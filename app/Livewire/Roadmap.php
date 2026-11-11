<?php

namespace App\Livewire;

use App\Contracts\ProgressServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The mentee's learning roadmap: finished fases, the active fase with its
 * modules, the next fase by name, and later fases hidden behind clouds.
 *
 * Locked fases are reduced to their names by ProgressService::focusRoadmap()
 * before rendering, so their module titles never reach the browser.
 */
#[Title('Roadmap Belajar')]
class Roadmap extends Component
{
    /**
     * Whether the names of the fases behind the clouds are revealed.
     */
    public bool $show_locked_fases = false;

    /**
     * Reveal the names (never the modules) of the locked fases.
     */
    public function showLockedFases(): void
    {
        $this->show_locked_fases = true;
    }

    /**
     * Hide the locked fase names behind the clouds again.
     */
    public function hideLockedFases(): void
    {
        $this->show_locked_fases = false;
    }

    /**
     * Render the roadmap page (route roadmap.show).
     *
     * @return View The livewire.roadmap view.
     */
    public function render(
        ProgressServiceInterface $progress_service,
        ScholarshipApplicationServiceInterface $application_service,
        ScholarshipPathServiceInterface $path_service,
    ): View {
        $user = $this->user();

        $queue = $application_service->orderedApplications($user);
        $active = $application_service->activeApplicationFrom($queue);
        $next = $path_service->nextApplicationFrom($queue, $active);

        $overview = $progress_service->overview($user, $active?->scholarship);
        $roadmap = $progress_service->focusRoadmap($overview->roadmap);

        return view('livewire.roadmap', [
            'user' => $user,
            'summary' => $overview->summary,
            'roadmap' => $roadmap,
            'active_application' => $active,
            'next_application' => $next,
            'queued_count' => max(0, $queue->count() - 1),
            'path_completed' => $active instanceof ScholarshipApplication && $overview->summary->isFinished(),
        ]);
    }

    /**
     * Get the authenticated mentee.
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
