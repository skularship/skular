<?php

namespace App\Services;

use App\Contracts\AdaptiveQuestionSelectorInterface;
use App\Contracts\AnswerCheckerInterface;
use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Data\TryoutAnswerResultData;
use App\Data\TryoutQuestionViewData;
use App\Data\TryoutUnitViewData;
use App\Enums\TryoutMode;
use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Serves questions and stores answers of a Try Out subtest.
 *
 * A question row is written before it is shown, so refreshing, reconnecting
 * or switching device shows the same question (and refreshing never finds
 * an easier one). Answers are accepted only for the active question, only
 * before the deadline (+ grace), under a lock on the subtest row, and are
 * stored before they are checked.
 */
class TryoutAnswerService implements TryoutAnswerServiceInterface
{
    /**
     * @param  AdaptiveQuestionSelectorInterface  $selector  Picks the next question.
     * @param  AnswerCheckerInterface  $checker  Checks answers against the key.
     * @param  TryoutAttemptServiceInterface  $attempts  Closes finished/expired subtests.
     */
    public function __construct(
        private readonly AdaptiveQuestionSelectorInterface $selector,
        private readonly AnswerCheckerInterface $checker,
        private readonly TryoutAttemptServiceInterface $attempts,
    ) {}

    /**
     * The question served but not answered yet, serving a new one when needed.
     */
    public function currentQuestion(TryoutAttemptSection $attempt_section): ?TryoutQuestionViewData
    {
        if ($this->attempts->closeExpired($attempt_section) || $attempt_section->status !== TryoutSectionStatus::InProgress) {
            return null;
        }

        $row = DB::transaction(function () use ($attempt_section): ?TryoutAnswer {
            $locked = TryoutAttemptSection::query()->whereKey($attempt_section->id)->lockForUpdate()->firstOrFail();

            return $this->pendingRow($locked) ?? $this->serveNext($locked);
        });

        $attempt_section->refresh();

        if ($row === null) {
            // Pool exhausted or question count reached: close the subtest.
            if ($attempt_section->status === TryoutSectionStatus::InProgress) {
                $this->attempts->submitSection($attempt_section);
            }

            return null;
        }

        return $this->viewOf($attempt_section, $row);
    }

    /**
     * Every prompt of a Writing/Speaking subtest in task/part order.
     *
     * @return array<int, TryoutQuestionViewData>
     */
    public function productivePrompts(TryoutAttemptSection $attempt_section): array
    {
        $rows = $attempt_section->answers()->with('question')->get();
        $total = $rows->count();

        return $rows->map(fn (TryoutAnswer $row): TryoutQuestionViewData => TryoutQuestionViewData::fromQuestion($row->question, $row->sequence, $total))
            ->values()
            ->all();
    }

    /**
     * Store and check a Listening/Reading answer, then serve the next question.
     *
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function answerObjective(TryoutAttemptSection $attempt_section, string $code, string $response): TryoutAnswerResultData
    {
        $response = mb_substr(trim($response), 0, 500);

        $this->guardDeadline($attempt_section);

        $outcome = DB::transaction(function () use ($attempt_section, $code, $response): array {
            $locked = $this->lockOpenSection($attempt_section);

            if (! $locked->skill->isObjective()) {
                throw new TryoutAnswerRejectedException('Subtes ini tidak memakai jawaban pilihan.');
            }

            $row = $this->pendingRow($locked);

            if ($row === null || $row->question->code !== $code) {
                throw new TryoutAnswerRejectedException;
            }

            $question = $row->question;
            $level = $locked->current_level ?? $locked->section->start_level;

            // Store first (AGENTS rule 9), then check.
            $row->forceFill([
                'response' => $response,
                'answered_at' => now(),
                'word_count' => $this->checker->countWords($response),
                'over_word_limit' => $question->word_limit !== null && $this->checker->countWords($response) > $question->word_limit,
                'time_spent_seconds' => $row->served_at === null ? null : (int) $row->served_at->diffInSeconds(now()),
            ])->save();

            $correct = $this->checker->isCorrect($question, $response);

            $row->forceFill(['is_correct' => $correct, 'score' => $correct ? $question->max_score : 0])->save();

            $locked->forceFill(['current_level' => $this->selector->nextLevel($level, $correct)])->save();

            $answered = $locked->answers()->whereNotNull('answered_at')->count();
            $finished = $answered >= $locked->section->question_count;
            $previous_unit = $locked->current_unit_id;
            $next = $finished ? null : $this->serveNext($locked);

            return [
                'answered' => $answered,
                'next' => $next,
                'finished' => $finished || $next === null,
                'new_block' => $next !== null && $next->question->tryout_unit_id !== $previous_unit,
            ];
        });

        $attempt_section->refresh();

        if ($outcome['finished']) {
            $this->attempts->submitSection($attempt_section);

            return new TryoutAnswerResultData(null, true, $outcome['answered']);
        }

        /** @var TryoutAnswer $next */
        $next = $outcome['next'];

        return new TryoutAnswerResultData(
            next_question: $this->viewOf($attempt_section, $next),
            section_finished: false,
            answered_count: $outcome['answered'],
            new_block: $outcome['new_block'],
        );
    }

    /**
     * Autosave a Writing task (every few seconds and when switching tasks).
     *
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function saveEssay(TryoutAttemptSection $attempt_section, string $code, string $text): TryoutAnswer
    {
        $text = mb_substr($text, 0, (int) config('tryout.writing.max_characters', 8000));

        $this->guardDeadline($attempt_section);

        return DB::transaction(function () use ($attempt_section, $code, $text): TryoutAnswer {
            $locked = $this->lockOpenSection($attempt_section);

            if ($locked->skill !== TryoutSkill::Writing) {
                throw new TryoutAnswerRejectedException('Subtes ini bukan Writing.');
            }

            $row = $this->rowByCode($locked, $code);

            $row->forceFill([
                'response' => $text,
                'word_count' => $this->checker->countWords($text),
                'answered_at' => now(),
            ])->save();

            return $row;
        });
    }

    /**
     * Store the transcript of a Speaking part, made by the mentee's browser (Web Speech API).
     *
     * No audio reaches the server. A new take of the same part replaces the
     * previous transcript. The text is untrusted data: it is trimmed, length
     * capped and only ever read by the assessor as data.
     *
     * @throws ValidationException
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function saveSpokenAnswer(TryoutAttemptSection $attempt_section, string $code, string $transcript, int $seconds): TryoutAnswer
    {
        $transcript = trim((string) preg_replace('/\s+/u', ' ', $transcript));

        Validator::make(['transcript' => $transcript, 'seconds' => $seconds], [
            'transcript' => ['nullable', 'string', 'max:'.(int) config('tryout.speaking.max_transcript_characters', 6000)],
            'seconds' => ['integer', 'min:0', 'max:'.(int) config('tryout.speaking.max_part_seconds', 600)],
        ], [], ['transcript' => 'transkrip', 'seconds' => 'durasi'])->validate();

        $this->guardDeadline($attempt_section);

        return DB::transaction(function () use ($attempt_section, $code, $transcript, $seconds): TryoutAnswer {
            $locked = $this->lockOpenSection($attempt_section);

            if ($locked->skill !== TryoutSkill::Speaking) {
                throw new TryoutAnswerRejectedException('Subtes ini bukan Speaking.');
            }

            $row = $this->rowByCode($locked, $code);

            $row->forceFill([
                'transcript' => $transcript,
                'word_count' => $this->checker->countWords($transcript),
                'time_spent_seconds' => $seconds,
                'answered_at' => now(),
            ])->save();

            return $row;
        });
    }

    /**
     * Count one Listening audio play of the active unit when the mode still allows it.
     */
    public function registerAudioPlay(TryoutAttemptSection $attempt_section, string $unit_code): bool
    {
        return DB::transaction(function () use ($attempt_section, $unit_code): bool {
            $locked = TryoutAttemptSection::query()->whereKey($attempt_section->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TryoutSectionStatus::InProgress || $locked->isExpired()) {
                return false;
            }

            if ($locked->currentUnit?->code !== $unit_code) {
                return false;
            }

            $allowed = $this->playsAllowed($locked);
            $plays = $locked->audio_plays ?? [];
            $used = (int) ($plays[$unit_code] ?? 0);

            if ($used >= $allowed) {
                return false;
            }

            $plays[$unit_code] = $used + 1;
            $locked->forceFill(['audio_plays' => $plays])->save();
            $attempt_section->audio_plays = $plays;

            return true;
        });
    }

    /**
     * Lock the subtest row and make sure it still accepts answers.
     *
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    private function lockOpenSection(TryoutAttemptSection $attempt_section): TryoutAttemptSection
    {
        $locked = TryoutAttemptSection::query()->whereKey($attempt_section->id)->lockForUpdate()->firstOrFail();

        // guardDeadline() already closed an expired subtest before the transaction;
        // this only catches a deadline passing in between.
        if ($locked->isExpired()) {
            throw new TryoutSectionExpiredException;
        }

        if ($locked->status !== TryoutSectionStatus::InProgress) {
            throw new TryoutAnswerRejectedException('Subtes ini sudah selesai.');
        }

        return $locked;
    }

    /**
     * Close an expired subtest and refuse the answer (the deadline is the server's, not the browser's).
     *
     * @throws TryoutSectionExpiredException
     */
    private function guardDeadline(TryoutAttemptSection $attempt_section): void
    {
        $fresh = $attempt_section->fresh() ?? $attempt_section;

        if ($fresh->status === TryoutSectionStatus::InProgress && $this->attempts->closeExpired($fresh)) {
            $attempt_section->refresh();

            throw new TryoutSectionExpiredException;
        }
    }

    /**
     * The served row of a question in a Writing/Speaking subtest.
     *
     * @throws TryoutAnswerRejectedException
     */
    private function rowByCode(TryoutAttemptSection $attempt_section, string $code): TryoutAnswer
    {
        $row = $attempt_section->answers()
            ->whereHas('question', fn ($query) => $query->where('code', $code))
            ->first();

        if ($row === null) {
            throw new TryoutAnswerRejectedException('Soal ini bukan bagian dari subtesmu.');
        }

        return $row;
    }

    /**
     * The served, unanswered row of an objective subtest.
     */
    private function pendingRow(TryoutAttemptSection $attempt_section): ?TryoutAnswer
    {
        return $attempt_section->answers()->whereNull('answered_at')->with('question')->first();
    }

    /**
     * Pick and store the next question of an objective subtest.
     */
    private function serveNext(TryoutAttemptSection $attempt_section): ?TryoutAnswer
    {
        if (! $attempt_section->skill->isObjective()) {
            return null;
        }

        $served = $attempt_section->answers()->count();

        if ($served >= $attempt_section->section->question_count) {
            return null;
        }

        $question = $this->selector->next($attempt_section);

        if (! $question instanceof TryoutQuestion) {
            return null;
        }

        $level = $attempt_section->current_level ?? $attempt_section->section->start_level;

        $row = TryoutAnswer::query()->create([
            'tryout_attempt_section_id' => $attempt_section->id,
            'tryout_question_id' => $question->id,
            'sequence' => $served + 1,
            'level_at_answer' => $level,
            'served_at' => now(),
        ]);

        if ($attempt_section->current_unit_id !== $question->tryout_unit_id) {
            $attempt_section->forceFill(['current_unit_id' => $question->tryout_unit_id])->save();
        }

        return $row->setRelation('question', $question);
    }

    /**
     * Build the mentee view of a served row (no key, no transcript).
     */
    private function viewOf(TryoutAttemptSection $attempt_section, TryoutAnswer $row): TryoutQuestionViewData
    {
        $question = $row->question;
        $unit = $question->unit;
        $section = $attempt_section->section;

        $unit_view = null;

        if ($unit instanceof TryoutUnit) {
            $block_total = max(1, (int) ceil($section->question_count / $section->perUnit()));

            $unit_view = TryoutUnitViewData::fromUnit(
                $unit,
                block_number: min($block_total, $this->blockNumber($attempt_section)),
                block_total: $block_total,
                plays_used: $attempt_section->playsOf($unit->code),
                plays_allowed: $this->playsAllowed($attempt_section),
            );
        }

        return TryoutQuestionViewData::fromQuestion($question, $row->sequence, $section->question_count, $unit_view);
    }

    /**
     * 1-based number of the block the latest served question belongs to.
     */
    private function blockNumber(TryoutAttemptSection $attempt_section): int
    {
        $blocks = 0;
        $previous = null;

        foreach ($attempt_section->answers()->with('question:id,tryout_unit_id')->get() as $row) {
            $unit_id = $row->question->tryout_unit_id;

            if ($unit_id !== $previous) {
                $blocks++;
                $previous = $unit_id;
            }
        }

        return max(1, $blocks);
    }

    /**
     * Audio plays per unit allowed by the attempt mode.
     */
    private function playsAllowed(TryoutAttemptSection $attempt_section): int
    {
        $section = $attempt_section->section;

        return $attempt_section->attempt->mode === TryoutMode::Exam
            ? $section->audio_plays_exam
            : $section->audio_plays_practice;
    }
}
