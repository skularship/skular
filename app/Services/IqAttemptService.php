<?php

namespace App\Services;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqScoringServiceInterface;
use App\Data\IqAnswerResultData;
use App\Data\IqQuestionViewData;
use App\Enums\ActivityName;
use App\Enums\IqAttemptStatus;
use App\Exceptions\IqAnswerRejectedException;
use App\Exceptions\IqNotAvailableException;
use App\Models\IqAnswer;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use App\Models\IqTest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Runs a Tes IQ attempt on server time. The active questions are frozen at
 * start with one answer row each (so a question in use can never be
 * deleted), the deadline is set on the server, every pick is saved under a
 * row lock and stays changeable until the attempt closes, the seconds spent
 * on each question are measured between server visits, and the scores are
 * computed on the server when the attempt closes ("Kirim" or deadline).
 */
class IqAttemptService implements IqAttemptServiceInterface
{
    /**
     * @param  IqScoringServiceInterface  $scoring  Scores a closed attempt.
     * @param  ActivityTrackerInterface  $tracker  Funnel events.
     */
    public function __construct(
        private readonly IqScoringServiceInterface $scoring,
        private readonly ActivityTrackerInterface $tracker,
    ) {}

    /**
     * The published test mentees take now (the newest one), or null.
     */
    public function currentTest(): ?IqTest
    {
        return IqTest::query()->published()->latest('id')->first();
    }

    /**
     * The mentee's attempt in progress, after closing it when its time ran out.
     */
    public function activeAttempt(User $user): ?IqAttempt
    {
        $attempt = $user->iqAttempts()
            ->where('status', IqAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if ($attempt === null || $this->closeIfExpired($attempt)) {
            return null;
        }

        return $attempt;
    }

    /**
     * Start an attempt, or return the one already in progress.
     *
     * The mentee row is locked so two clicks or two tabs never create two
     * active attempts (one active attempt per account).
     *
     * @throws IqNotAvailableException When the test is a draft or has no active questions.
     */
    public function start(User $user, IqTest $test): IqAttempt
    {
        $active = $this->activeAttempt($user);

        if ($active !== null) {
            return $active;
        }

        $created = false;

        $attempt = DB::transaction(function () use ($user, $test, &$created): IqAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $running = $user->iqAttempts()
                ->where('status', IqAttemptStatus::InProgress->value)
                ->latest('id')
                ->first();

            if ($running !== null && ! $running->isPastDeadline()) {
                return $running;
            }

            if (! $test->status->isPublished()) {
                throw new IqNotAvailableException('Tes IQ ini belum dibuka.');
            }

            $ids = $test->questions()->where('is_active', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

            if ($ids === []) {
                throw new IqNotAvailableException('Soal Tes IQ sedang disiapkan. Coba lagi nanti ya.');
            }

            $now = now();

            $attempt = IqAttempt::query()->create([
                'user_id' => $user->id,
                'iq_test_id' => $test->id,
                'status' => IqAttemptStatus::InProgress,
                'question_ids' => $ids,
                'started_at' => $now,
                'deadline_at' => $now->addMinutes($test->duration_minutes),
                'total_questions' => count($ids),
            ]);

            foreach ($ids as $question_id) {
                IqAnswer::query()->create([
                    'iq_attempt_id' => $attempt->id,
                    'iq_question_id' => $question_id,
                ]);
            }

            $created = true;

            return $attempt;
        });

        if ($created) {
            $this->tracker->track(ActivityName::IqStarted, ['iq_test_id' => $test->id], $user);
        }

        return $attempt;
    }

    /**
     * Show the question at a position and record the time spent on the previous one.
     */
    public function question(IqAttempt $attempt, int $position): IqQuestionViewData
    {
        $ids = $attempt->question_ids;
        $total = count($ids);
        $position = max(1, min($total, $position));
        $question_id = (int) $ids[$position - 1];

        if ($attempt->status === IqAttemptStatus::InProgress && ! $attempt->isPastDeadline()) {
            DB::transaction(function () use ($attempt, $question_id): void {
                $locked = IqAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== IqAttemptStatus::InProgress || $locked->current_question_id === $question_id) {
                    return;
                }

                $this->flushVisit($locked);
                $locked->forceFill(['current_question_id' => $question_id, 'current_viewed_at' => now()])->save();
            });
        }

        $question = IqQuestion::query()->findOrFail($question_id);
        $choice = IqAnswer::query()
            ->where('iq_attempt_id', $attempt->id)
            ->where('iq_question_id', $question_id)
            ->value('choice');

        return IqQuestionViewData::fromQuestion($question, $position, $total, is_string($choice) ? $choice : null);
    }

    /**
     * Save (or change) the choice of the question at a position.
     *
     * @throws IqAnswerRejectedException
     */
    public function answer(IqAttempt $attempt, int $position, string $choice): IqAnswerResultData
    {
        $choice = strtoupper(trim($choice));
        $ids = $attempt->question_ids;
        $total = count($ids);

        if (! in_array($choice, IqQuestion::LETTERS, true)) {
            throw new IqAnswerRejectedException('Pilih salah satu jawaban A, B, C, atau D.');
        }

        if ($position < 1 || $position > $total) {
            throw new IqAnswerRejectedException('Soal ini tidak ada di tesmu.');
        }

        if ($this->closeIfExpired($attempt)) {
            throw new IqAnswerRejectedException('Waktu tes sudah habis. Jawaban yang tersimpan sebelum waktu habis tetap dihitung.');
        }

        $question_id = (int) $ids[$position - 1];

        return DB::transaction(function () use ($attempt, $question_id, $choice, $total): IqAnswerResultData {
            $locked = IqAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== IqAttemptStatus::InProgress) {
                throw new IqAnswerRejectedException('Tes ini sudah dikirim. Jawaban tidak bisa diubah lagi.');
            }

            if ($locked->isPastDeadline()) {
                throw new IqAnswerRejectedException('Waktu tes sudah habis. Jawaban yang tersimpan sebelum waktu habis tetap dihitung.');
            }

            $answer = IqAnswer::query()->firstOrCreate(['iq_attempt_id' => $locked->id, 'iq_question_id' => $question_id]);
            $before = $locked->answers()->whereNotNull('choice')->count();
            $was_answered = $answer->choice !== null;

            $answer->forceFill([
                'choice' => $choice,
                'answered_at' => now(),
                'changes' => $answer->changes + ($was_answered && $answer->choice !== $choice ? 1 : 0),
            ])->save();

            $after = $was_answered ? $before : $before + 1;

            return new IqAnswerResultData(
                answered_count: $after,
                total: $total,
                support_message: $this->supportMessage($locked, $question_id, $before, $after),
            );
        });
    }

    /**
     * Positions (1-based) that have a saved choice.
     *
     * @return array<int, int>
     */
    public function answeredPositions(IqAttempt $attempt): array
    {
        $positions = array_flip(array_map('intval', $attempt->question_ids));

        return $attempt->answers()
            ->whereNotNull('choice')
            ->pluck('iq_question_id')
            ->map(fn (mixed $id): ?int => isset($positions[(int) $id]) ? $positions[(int) $id] + 1 : null)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Close the attempt on "Kirim" (or as expired when the time already ran out) and score it.
     */
    public function finish(IqAttempt $attempt): void
    {
        $this->close($attempt, $attempt->isPastDeadline() ? IqAttemptStatus::Expired : IqAttemptStatus::Completed);
    }

    /**
     * Close and score the attempt when its server deadline passed.
     */
    public function closeIfExpired(IqAttempt $attempt): bool
    {
        if ($attempt->status !== IqAttemptStatus::InProgress || ! $attempt->isPastDeadline()) {
            return false;
        }

        return $this->close($attempt, IqAttemptStatus::Expired);
    }

    /**
     * Close an attempt once (row lock), record the last visit and score it.
     */
    private function close(IqAttempt $attempt, IqAttemptStatus $status): bool
    {
        $closed = DB::transaction(function () use ($attempt, $status): bool {
            $locked = IqAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== IqAttemptStatus::InProgress) {
                return false;
            }

            $this->flushVisit($locked);

            $locked->forceFill([
                'status' => $status,
                'completed_at' => $status === IqAttemptStatus::Expired ? $locked->deadline_at : now(),
                'current_question_id' => null,
                'current_viewed_at' => null,
            ])->save();

            $this->scoring->score($locked);

            return true;
        });

        $attempt->refresh();

        if ($closed) {
            $this->tracker->track(ActivityName::IqCompleted, ['iq_test_id' => $attempt->iq_test_id, 'status' => $status->value], $attempt->user);
        }

        return $closed;
    }

    /**
     * Add the seconds since the current question was shown to its answer row
     * (capped per visit and at the deadline). Internal data only.
     */
    private function flushVisit(IqAttempt $locked): void
    {
        if ($locked->current_question_id === null || $locked->current_viewed_at === null) {
            return;
        }

        $end = now()->lessThan($locked->deadline_at) ? now() : $locked->deadline_at;
        $seconds = (int) min(
            max(0, $locked->current_viewed_at->diffInSeconds($end)),
            (int) config('iq.max_seconds_per_visit', 1200),
        );

        if ($seconds > 0) {
            IqAnswer::query()
                ->where('iq_attempt_id', $locked->id)
                ->where('iq_question_id', $locked->current_question_id)
                ->increment('time_spent_seconds', $seconds);
        }
    }

    /**
     * Support message when the answered count crosses a milestone (25/50/75%).
     * Built only from the mentee's own counts: no correctness, no praise,
     * no comparison with other mentees.
     */
    private function supportMessage(IqAttempt $attempt, int $question_id, int $before, int $after): ?string
    {
        $total = count($attempt->question_ids);
        $crossed = false;

        foreach ((array) config('iq.support_milestones', [25, 50, 75]) as $percent) {
            $threshold = (int) ceil($total * (int) $percent / 100);

            if ($threshold > 0 && $before < $threshold && $after >= $threshold) {
                $crossed = true;
            }
        }

        if (! $crossed) {
            return null;
        }

        $message = __('Kamu sudah menjawab :answered dari :total soal.', ['answered' => $after, 'total' => $total]);
        $question = IqQuestion::query()->find($question_id);

        if ($question === null) {
            return $message;
        }

        $subtest_ids = IqQuestion::query()
            ->whereIn('id', $attempt->question_ids)
            ->where('subtest', $question->subtest->value)
            ->pluck('id');

        $answered_in_subtest = $attempt->answers()
            ->whereIn('iq_question_id', $subtest_ids)
            ->whereNotNull('choice')
            ->count();

        return $message.' '.__('Di bagian :subtest, kamu sudah menjawab :answered dari :total soal.', [
            'subtest' => $question->subtest->getLabel(),
            'answered' => $answered_in_subtest,
            'total' => $subtest_ids->count(),
        ]);
    }
}
