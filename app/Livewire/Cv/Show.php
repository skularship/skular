<?php

namespace App\Livewire\Cv;

use App\Contracts\CvServiceInterface;
use App\Data\CvSectionData;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Shows the mentee CV built from practice forms the admin flagged for the CV.
 *
 * @property-read Collection<int, CvSectionData> $sections
 * @property-read User $profile
 */
#[Title('CV Saya')]
class Show extends Component
{
    /**
     * Get the mentee profile data displayed on the CV header.
     */
    #[Computed]
    public function profile(): User
    {
        return $this->user();
    }

    /**
     * Get the CV sections sourced from practice answers.
     *
     * @return Collection<int, CvSectionData>
     */
    #[Computed]
    public function sections(): Collection
    {
        return app(CvServiceInterface::class)->sections($this->user());
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

    public function render(): View
    {
        return view('livewire.cv.show', [
            'sections' => $this->sections,
            'profile' => $this->profile,
        ]);
    }
}
