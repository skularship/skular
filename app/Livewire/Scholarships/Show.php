<?php

namespace App\Livewire\Scholarships;

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public-to-mentees detail page for a single scholarship.
 */
#[Title('Detail Beasiswa')]
class Show extends Component
{
    public Scholarship $scholarship;

    public function mount(Scholarship $scholarship): void
    {
        // Draft and archived scholarships are not mentee-facing.
        if (! in_array($scholarship->status, ScholarshipStatus::publiclyVisible(), true)) {
            abort(404);
        }

        $this->scholarship = $scholarship->loadMissing('cluster');
    }

    /**
     * Add the scholarship to the mentee's preparation list.
     *
     * The cap and the open window are re-checked server-side by the service,
     * so a manipulated request can never bypass them.
     */
    public function addToSelection(ScholarshipApplicationServiceInterface $application_service): void
    {
        /** @var User $user */
        $user = Auth::user();

        $application = $this->application($user);

        if ($application instanceof ScholarshipApplication) {
            $this->dispatch('toast', variant: 'info', text: __('Beasiswa ini sudah ada di daftarmu.'));

            return;
        }

        if (! $this->scholarship->isOpen()) {
            $this->addError('selection', __('Pendaftaran beasiswa ini sudah ditutup.'));

            return;
        }

        if ($application_service->hasReachedLimit($user)) {
            $this->addError('selection', __('Kamu hanya boleh memilih maksimal :count beasiswa.', [
                'count' => ScholarshipApplicationService::MAX_SELECTIONS,
            ]));

            return;
        }

        $added = $application_service->select($user, [$this->scholarship->id]);

        if ($added === 0) {
            $this->addError('selection', __('Beasiswa ini tidak bisa ditambahkan saat ini.'));

            return;
        }

        $this->dispatch('toast', variant: 'success', text: __('Beasiswa berhasil ditambahkan ke daftar persiapanmu.'));
    }

    public function render(ScholarshipApplicationServiceInterface $application_service): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.scholarships.show', [
            'application' => $this->application($user),
            'at_limit' => $application_service->hasReachedLimit($user),
            'remaining_slots' => $application_service->remainingSlots($user),
            'max_selections' => ScholarshipApplicationService::MAX_SELECTIONS,
            'active_application_id' => $application_service->activeApplication($user)?->scholarship_id,
        ]);
    }

    /**
     * Get the mentee's application for this scholarship, when it exists.
     */
    private function application(User $user): ?ScholarshipApplication
    {
        return $user->scholarshipApplications()
            ->where('scholarship_id', $this->scholarship->id)
            ->first();
    }
}
