<?php

namespace App\Livewire\Tryout;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\EntitlementServiceInterface;
use App\Contracts\SpeakingAssessmentServiceInterface;
use App\Contracts\TryoutResultServiceInterface;
use App\Contracts\WritingAssessmentServiceInterface;
use App\Enums\ActivityName;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutSkill;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Free result page of a Try Out attempt (route tryout.result): overall band,
 * one card per subtest (band, raw score, final level), Writing/Speaking
 * criteria, the theory/material cards and the Pro call to action. While the
 * Writing/Speaking AI is still pending, the page runs one AI step per request
 * (wire:init + polling, D6) and offers "Nilai ulang" when a step failed.
 */
#[Title('Hasil Try Out')]
class Result extends Component
{
    #[Locked]
    public int $attempt_id;

    /**
     * Load the mentee's own attempt; one still running goes back to the runner.
     */
    public function mount(TryoutAttempt $attempt): void
    {
        abort_unless($attempt->isOwnedBy($this->user()), 403);

        $this->attempt_id = $attempt->id;

        if ($attempt->status === TryoutAttemptStatus::InProgress) {
            $this->redirectRoute('tryout.runner', ['attempt' => $attempt->id], navigate: false);
        }
    }

    /**
     * Run the next pending Writing/Speaking AI step (one provider call per request).
     */
    public function advanceAssessment(WritingAssessmentServiceInterface $writing, SpeakingAssessmentServiceInterface $speaking): void
    {
        $attempt = $this->attempt();

        foreach ($attempt->sections()->get() as $section) {
            $ran = match ($section->skill) {
                TryoutSkill::Writing => $writing->assessNext($section),
                TryoutSkill::Speaking => $speaking->nextStep($section),
                default => false,
            };

            if ($ran) {
                return;
            }
        }
    }

    /**
     * "Nilai ulang" of one Writing task.
     */
    public function retryWriting(int $answer_id, WritingAssessmentServiceInterface $writing): void
    {
        $answer = TryoutAnswer::query()
            ->whereKey($answer_id)
            ->whereHas('attemptSection', fn ($query) => $query->where('tryout_attempt_id', $this->attempt()->id)->where('skill', TryoutSkill::Writing->value))
            ->firstOrFail();

        $writing->retry($answer);
    }

    /**
     * "Nilai ulang" of the Speaking subtest (failed transcription first).
     */
    public function retrySpeaking(SpeakingAssessmentServiceInterface $speaking): void
    {
        $section = $this->attempt()->sections()->where('skill', TryoutSkill::Speaking->value)->firstOrFail();

        $speaking->retry($section);
    }

    /**
     * Log the click on the paid material link, then open it.
     */
    public function openMaterials(ActivityTrackerInterface $tracker): void
    {
        $tryout = $this->attempt()->tryout;

        if (! $tryout->hasMaterialsLink()) {
            return;
        }

        $tracker->track(ActivityName::TryoutMaterialsClicked, ['tryout_id' => $tryout->id, 'from' => 'result'], $this->user());

        $this->redirect((string) $tryout->materials_url);
    }

    /**
     * Render the result.
     *
     * @return View The livewire.tryout.result view.
     */
    public function render(TryoutResultServiceInterface $results, EntitlementServiceInterface $entitlements): View
    {
        $attempt = $this->attempt()->load('tryout.theoryModule');
        $user = $this->user();

        return view('livewire.tryout.result', [
            'attempt' => $attempt,
            'tryout' => $attempt->tryout,
            'result' => $results->result($attempt, $user),
            'can_theory' => $entitlements->can($user, 'tryout_theory'),
            'can_mentor' => $entitlements->can($user, 'tryout_mentor'),
            'premium_enabled' => $entitlements->premiumEnabled(),
        ]);
    }

    /**
     * The attempt, re-checked against the signed-in mentee on every request.
     */
    private function attempt(): TryoutAttempt
    {
        $attempt = TryoutAttempt::query()->with('tryout')->findOrFail($this->attempt_id);

        abort_unless($attempt->isOwnedBy($this->user()), 403);

        return $attempt;
    }

    /**
     * The signed-in mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
