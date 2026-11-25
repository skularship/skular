<?php

namespace App\Services;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Contracts\TryoutCatalogServiceInterface;
use App\Contracts\TryoutScoringServiceInterface;
use App\Enums\ActivityName;
use App\Enums\AiFeedbackStatus;
use App\Enums\ContentStatus;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use App\Exceptions\TryoutNotAvailableException;
use App\Models\Tryout;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use App\Models\TryoutSection;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Runs a Try Out attempt: creates it with one row per subtest, opens the
 * subtests in `ordering` order with a server deadline, closes them (mentee
 * or deadline), scores objective subtests and completes the attempt.
 * Every state change happens under a row lock.
 */
class TryoutAttemptService implements TryoutAttemptServiceInterface
{
    /**
     * @param  TryoutCatalogServiceInterface  $catalog  Start rules.
     * @param  TryoutScoringServiceInterface  $scoring  Scores and bands.
     * @param  ActivityTrackerInterface  $tracker  Funnel events.
     */
    public function __construct(
        private readonly TryoutCatalogServiceInterface $catalog,
        private readonly TryoutScoringServiceInterface $scoring,
        private readonly ActivityTrackerInterface $tracker,
    ) {}

    /**
     * Start an attempt (or return the one in progress) and open its first subtest.
     *
     * The mentee row is locked so two clicks or two tabs never create two
     * active attempts or go past the attempt limit.
     *
     * @throws TryoutNotAvailableException
     */
    public function start(User $user, Tryout $tryout, TryoutMode $mode): TryoutAttempt
    {
        $attempt = DB::transaction(function () use ($user, $tryout, $mode): TryoutAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $active = $this->catalog->activeAttempt($user, $tryout);

            if ($active !== null) {
                return $active;
            }

            $reason = $this->catalog->blockedReason($user, $tryout);

            if ($reason !== null) {
                throw new TryoutNotAvailableException($reason);
            }

            $sections = $tryout->sections()->get();

            $attempt = TryoutAttempt::query()->create([
                'user_id' => $user->id,
                'tryout_id' => $tryout->id,
                'mode' => $mode,
                'status' => TryoutAttemptStatus::InProgress,
                'current_skill' => $sections->first()?->skill,
                'start_level' => (int) config('tryout.adaptive.start_level', 3),
                'started_at' => now(),
            ]);

            foreach ($sections as $section) {
                TryoutAttemptSection::query()->create([
                    'tryout_attempt_id' => $attempt->id,
                    'tryout_section_id' => $section->id,
                    'skill' => $section->skill,
                    'status' => TryoutSectionStatus::Pending,
                ]);
            }

            $this->tracker->track(ActivityName::TryoutStarted, ['tryout_id' => $tryout->id, 'mode' => $mode->value], $user);

            return $attempt;
        });

        $this->startSection($attempt);

        return $attempt->refresh();
    }

    /**
     * The subtest the mentee is on, closing expired ones first.
     */
    public function currentSection(TryoutAttempt $attempt): ?TryoutAttemptSection
    {
        foreach ($attempt->sections()->with('section')->get() as $attempt_section) {
            if ($attempt_section->status === TryoutSectionStatus::InProgress && $this->closeExpired($attempt_section)) {
                continue;
            }

            if (! $attempt_section->status->isClosed() && ! $attempt_section->refresh()->status->isClosed()) {
                return $attempt_section;
            }
        }

        return null;
    }

    /**
     * Open the current subtest: start the timer and serve Writing/Speaking prompts.
     *
     * Idempotent: a subtest already in progress is returned unchanged.
     */
    public function startSection(TryoutAttempt $attempt): ?TryoutAttemptSection
    {
        $current = $this->currentSection($attempt);

        if ($current === null) {
            $this->complete($attempt);

            return null;
        }

        return DB::transaction(function () use ($attempt, $current): TryoutAttemptSection {
            $locked = TryoutAttemptSection::query()->whereKey($current->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TryoutSectionStatus::Pending) {
                return $locked;
            }

            $section = $locked->section;
            $now = now();

            $locked->forceFill([
                'status' => TryoutSectionStatus::InProgress,
                'started_at' => $now,
                'deadline_at' => $now->addSeconds($section->totalSeconds()),
                'current_level' => $section->start_level,
            ])->save();

            $attempt->forceFill(['current_skill' => $locked->skill])->save();

            if (! $locked->skill->isObjective()) {
                $this->serveProductivePrompts($locked, $attempt);
            }

            return $locked;
        });
    }

    /**
     * Close a subtest, score it if objective, and move the attempt on.
     */
    public function submitSection(TryoutAttemptSection $attempt_section): void
    {
        $closed = DB::transaction(function () use ($attempt_section): bool {
            $locked = TryoutAttemptSection::query()->whereKey($attempt_section->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isClosed()) {
                return false;
            }

            $locked->forceFill([
                'status' => TryoutSectionStatus::Submitted,
                'submitted_at' => now(),
                'final_level' => $locked->current_level,
            ])->save();

            // A served question the mentee never answered counts as 0; its
            // row is dropped so the review lists answered questions only.
            if ($locked->skill->isObjective()) {
                $locked->answers()->whereNull('answered_at')->delete();
                $this->scoring->scoreObjective($locked);
            } else {
                // Writing is graded per task (empty tasks too); Speaking once for the whole subtest.
                if ($locked->skill === TryoutSkill::Writing) {
                    $locked->answers()->whereNull('ai_status')->update(['ai_status' => AiFeedbackStatus::Pending->value]);
                } else {
                    $locked->forceFill(['ai_status' => AiFeedbackStatus::Pending])->save();
                }
            }

            return true;
        });

        $attempt_section->refresh();

        if (! $closed) {
            return;
        }

        $attempt = $attempt_section->attempt;
        $next = $attempt->sections()->where('status', TryoutSectionStatus::Pending->value)->first();

        if ($next === null) {
            $this->complete($attempt);

            return;
        }

        // The next subtest opens on the transition screen ("Lanjut"); its timer starts then.
        $attempt->forceFill(['current_skill' => $next->skill])->save();
    }

    /**
     * Close the subtest when its deadline plus grace has passed.
     */
    public function closeExpired(TryoutAttemptSection $attempt_section): bool
    {
        if ($attempt_section->status !== TryoutSectionStatus::InProgress || ! $attempt_section->isExpired()) {
            return false;
        }

        $this->submitSection($attempt_section);

        return true;
    }

    /**
     * Mark the attempt completed and compute the overall band when possible.
     */
    public function complete(TryoutAttempt $attempt): void
    {
        $completed = DB::transaction(function () use ($attempt): bool {
            $locked = TryoutAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TryoutAttemptStatus::InProgress) {
                return false;
            }

            $locked->forceFill([
                'status' => TryoutAttemptStatus::Completed,
                'completed_at' => now(),
                'current_skill' => null,
            ])->save();

            return true;
        });

        $attempt->refresh();

        if ($completed) {
            $this->scoring->overall($attempt);
            $this->tracker->track(ActivityName::TryoutCompleted, ['tryout_id' => $attempt->tryout_id], $attempt->user);
        }
    }

    /**
     * Serve one prompt per Writing task / Speaking part at the productive level.
     *
     * The rows are written before the prompts are shown, so a refresh shows
     * the same prompts (D13: the level comes from the start level in v1).
     */
    private function serveProductivePrompts(TryoutAttemptSection $attempt_section, TryoutAttempt $attempt): void
    {
        if ($attempt_section->answers()->exists()) {
            return;
        }

        $section = $attempt_section->section;
        $level = $this->productiveLevel($attempt);
        $pool = $this->productivePool($section);
        $parts = $pool->pluck('part')->filter()->unique()->sort()->values()->take($section->question_count);

        $sequence = 1;

        foreach ($parts as $part) {
            /** @var TryoutQuestion|null $question */
            $question = $pool->where('part', $part)
                ->sortBy(fn (TryoutQuestion $candidate): array => [abs($candidate->level - $level), $candidate->level, $candidate->id])
                ->first();

            if ($question === null) {
                continue;
            }

            TryoutAnswer::query()->create([
                'tryout_attempt_section_id' => $attempt_section->id,
                'tryout_question_id' => $question->id,
                'sequence' => $sequence++,
                'level_at_answer' => $question->level,
                'served_at' => now(),
            ]);
        }
    }

    /**
     * Published Writing/Speaking prompts of the section pool.
     *
     * @return Collection<int, TryoutQuestion>
     */
    private function productivePool(TryoutSection $section): Collection
    {
        return $section->poolQuestions()
            ->where('status', ContentStatus::Published->value)
            ->whereNotNull('part')
            ->get()
            ->toBase();
    }

    /**
     * Level of the Writing/Speaking prompts (config tryout.productive_level_source).
     */
    private function productiveLevel(TryoutAttempt $attempt): int
    {
        if (config('tryout.productive_level_source') === 'final_lr') {
            $levels = $attempt->sections()
                ->whereIn('skill', [TryoutSkill::Listening->value, TryoutSkill::Reading->value])
                ->whereNotNull('final_level')
                ->pluck('final_level');

            if ($levels->isNotEmpty()) {
                return (int) round((float) $levels->avg());
            }
        }

        return $attempt->start_level;
    }
}
