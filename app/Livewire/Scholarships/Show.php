<?php

namespace App\Livewire\Scholarships;

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Contracts\ScholarshipReportServiceInterface;
use App\Data\UserProfileData;
use App\Enums\ReportedField;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public-to-mentees detail page for a single scholarship: data, match
 * reasons, adding it to the selection, and the "Informasi Tidak Akurat?"
 * report form.
 */
#[Title('Detail Beasiswa')]
class Show extends Component
{
    public Scholarship $scholarship;

    /**
     * Whether the "Informasi Tidak Akurat?" modal is open.
     */
    public bool $show_report_modal = false;

    /**
     * Reported part of the page (ReportedField value).
     */
    public string $report_field = '';

    /**
     * What the mentee believes is correct.
     */
    public string $report_suggested_value = '';

    /**
     * Optional evidence link, stored for admins only.
     */
    public string $report_source_url = '';

    /**
     * Optional extra note.
     */
    public string $report_note = '';

    /**
     * Send an "Informasi Tidak Akurat?" report for this scholarship.
     *
     * Validation, sanitising and the anti-spam limits all live in the
     * service, so a crafted request cannot skip them.
     */
    public function submitReport(ScholarshipReportServiceInterface $report_service): void
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $report_service->submit($user, $this->scholarship, [
                'field' => $this->report_field,
                'suggested_value' => $this->report_suggested_value,
                'source_url' => $this->report_source_url,
                'note' => $this->report_note,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($key === 'report' ? 'report' : 'report_'.$key, $messages[0]);
            }

            return;
        }

        $this->reset('report_field', 'report_suggested_value', 'report_source_url', 'report_note', 'show_report_modal');
        $this->resetErrorBag();

        $this->dispatch('toast', variant: 'success', text: __('Terima kasih, laporanmu kami cek.'));
    }

    /**
     * Show one scholarship; draft and archived scholarships return 404.
     *
     * @param  Scholarship  $scholarship  Route-bound scholarship.
     */
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

    /**
     * Render the scholarship detail page (route scholarships.show).
     *
     * @return View The livewire.scholarships.show view.
     */
    public function render(
        ScholarshipApplicationServiceInterface $application_service,
        ScholarshipMatchingServiceInterface $matching_service,
    ): View {
        /** @var User $user */
        $user = Auth::user();

        // The match label and reasons moved here from the recommendation card;
        // they only make sense once the mentee finished profiling.
        $match = $user->hasCompletedProfile()
            ? $matching_service->matchOne(UserProfileData::fromUser($user->loadMissing('cluster')), $this->scholarship)
            : null;

        return view('livewire.scholarships.show', [
            'match' => $match,
            'report_fields' => ReportedField::options(),
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
