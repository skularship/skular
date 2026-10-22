<?php

namespace App\Livewire\Scholarships;

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Tracks every scholarship a mentee selected: external submission
 * confirmation with a screenshot proof, then the reported result.
 */
#[Title('Beasiswa Saya')]
class Tracker extends Component
{
    use WithFileUploads;

    /**
     * Application currently open in the submission confirmation panel.
     */
    public ?int $confirming_id = null;

    /**
     * Application currently open in the result reporting panel.
     */
    public ?int $reporting_id = null;

    /**
     * Screenshot proving the mentee submitted on the scholarship website.
     *
     * @var UploadedFile|string|null
     */
    public $proof;

    public ?string $note = null;

    public ?string $result_note = null;

    /**
     * Open the submission confirmation panel for an application.
     */
    public function openConfirm(int $application_id): void
    {
        $this->application($application_id);

        $this->confirming_id = $application_id;
        $this->reporting_id = null;
        $this->proof = null;
        $this->note = null;
    }

    /**
     * Close the submission confirmation panel.
     */
    public function cancelConfirm(): void
    {
        $this->confirming_id = null;
        $this->proof = null;
        $this->note = null;
        $this->resetErrorBag();
    }

    /**
     * Store the screenshot proof and mark the application as submitted.
     */
    public function confirmSubmission(ScholarshipApplicationServiceInterface $application_service): void
    {
        if ($this->confirming_id === null) {
            return;
        }

        $application = $this->application($this->confirming_id);

        $this->validate([
            'proof' => ['required', 'file', 'image', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $proof = $this->proof instanceof UploadedFile ? $this->proof : null;

        $application_service->confirmSubmission($this->user(), $application, $proof, $this->note);

        $this->cancelConfirm();

        $this->dispatch('toast', variant: 'success', text: __('Konfirmasi pendaftaranmu tersimpan. Tinggal menunggu hasilnya!'));
    }

    /**
     * Open the result reporting panel for an application.
     */
    public function openReport(int $application_id): void
    {
        $this->application($application_id);

        $this->reporting_id = $application_id;
        $this->confirming_id = null;
        $this->result_note = null;
    }

    /**
     * Close the result reporting panel.
     */
    public function cancelReport(): void
    {
        $this->reporting_id = null;
        $this->result_note = null;
        $this->resetErrorBag();
    }

    /**
     * Report whether the mentee passed the scholarship selection.
     */
    public function reportResult(
        int $application_id,
        string $result,
        ScholarshipApplicationServiceInterface $application_service,
    ): void {
        $application = $this->application($application_id);
        $parsed = ApplicationResult::tryFrom($result);

        if ($parsed === null) {
            return;
        }

        $application_service->reportResult($this->user(), $application, $parsed);

        $this->cancelReport();

        $this->dispatch(
            'toast',
            variant: $parsed === ApplicationResult::Accepted ? 'success' : 'info',
            text: $parsed === ApplicationResult::Accepted
                ? __('Selamat! Twibbon kini bisa kamu buat. 🎉')
                : __('Terima kasih sudah melapor. Terus berjuang ya!'),
        );
    }

    /**
     * Get the mentee's scholarship applications for the tracker.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    private function applications()
    {
        return $this->user()
            ->scholarshipApplications()
            ->with('scholarship')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve one of the mentee's applications or fail with 404.
     */
    private function application(int $application_id): ScholarshipApplication
    {
        return $this->user()
            ->scholarshipApplications()
            ->with('scholarship')
            ->findOrFail($application_id);
    }

    /**
     * Determine whether the application still has to be confirmed.
     */
    public function needsSubmission(ScholarshipApplication $application): bool
    {
        return in_array($application->status, [ApplicationStatus::Selected, ApplicationStatus::InProgress], true);
    }

    /**
     * Determine whether the mentee still has to report the result.
     */
    public function needsResult(ScholarshipApplication $application): bool
    {
        return $application->status === ApplicationStatus::Submitted;
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
        return view('livewire.scholarships.tracker', [
            'applications' => $this->applications(),
        ]);
    }
}
