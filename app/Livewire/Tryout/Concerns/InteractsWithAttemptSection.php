<?php

namespace App\Livewire\Tryout\Concerns;

use App\Contracts\TryoutAttemptServiceInterface;
use App\Models\TryoutAttemptSection;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Shared plumbing of the Try Out subtest components: loads the subtest row
 * owned by the signed-in mentee, closes it ("Selesaikan subtes" or the
 * deadline reached in the browser) and tells the Runner it closed.
 *
 * @property int $attempt_section_id
 */
trait InteractsWithAttemptSection
{
    /**
     * Close the subtest on the mentee's request (unanswered questions count as 0).
     */
    public function finishSection(TryoutAttemptServiceInterface $attempts): void
    {
        $attempts->submitSection($this->attemptSection());
        $this->dispatch('section-closed');
    }

    /**
     * Called by the timer when the deadline passed; the server decides.
     */
    public function expire(TryoutAttemptServiceInterface $attempts): void
    {
        $section = $this->attemptSection();

        if ($attempts->closeExpired($section) || $section->status->isClosed()) {
            $this->dispatch('toast', variant: 'info', text: __('Waktu subtes ini sudah habis. Jawabanmu yang sudah terkirim tetap tersimpan.'));
            $this->dispatch('section-closed');
        }
    }

    /**
     * The subtest row, re-checked against the signed-in mentee on every request.
     */
    protected function attemptSection(): TryoutAttemptSection
    {
        $section = TryoutAttemptSection::query()->with(['section.tryout', 'attempt'])->findOrFail($this->attempt_section_id);
        $user = Auth::user();

        abort_unless($user instanceof User && $section->attempt->isOwnedBy($user), 403);

        return $section;
    }

    /**
     * Whether the mentee exceeded a per-minute action limit.
     */
    protected function tooManyAttempts(string $action, int $per_minute): bool
    {
        $key = 'tryout:'.$action.':'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, $per_minute)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    /**
     * Header data shared by the three subtest views.
     *
     * @return array{deadline: string|null, server_now: string, grace: int, skills: array<int, array{label: string, state: string}>, tryout_code: string}
     */
    protected function headerData(TryoutAttemptSection $section): array
    {
        $skills = $section->attempt->sections()->get()->map(fn (TryoutAttemptSection $row): array => [
            'label' => $row->skill->getLabel(),
            'state' => $row->id === $section->id ? 'current' : ($row->status->isClosed() ? 'done' : 'next'),
        ])->all();

        return [
            'deadline' => $section->deadline_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'grace' => (int) config('tryout.answer_grace_seconds', 5),
            'skills' => $skills,
            'tryout_code' => $section->section->tryout->code,
        ];
    }
}
