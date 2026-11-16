<?php

namespace App\Jobs;

use App\Contracts\EntitlementServiceInterface;
use App\Enums\ApplicationStatus;
use App\Mail\DeadlineReminderMail;
use App\Models\ReminderLog;
use App\Models\ScholarshipApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one deadline reminder email for one application, at most once per offset (7, 3 or 1 day).
 *
 * Gratis mentees get reminders only for their primary scholarship; Pro gets all of them.
 */
class SendDeadlineReminder implements ShouldQueue
{
    use Queueable;

    /**
     * Attempts before the job is parked in failed_jobs.
     */
    public int $tries = 3;

    /**
     * Keep the application id and the offset, and use the reminders queue.
     */
    public function __construct(public readonly int $applicationId, public readonly int $daysBefore)
    {
        $this->onQueue('reminders');
    }

    /**
     * Send the email unless it was sent, the mentee already submitted, or the plan does not cover it.
     */
    public function handle(EntitlementServiceInterface $entitlements): void
    {
        $application = ScholarshipApplication::query()->with(['scholarship', 'user'])->find($this->applicationId);

        if (! $application instanceof ScholarshipApplication || ! in_array($application->status, [ApplicationStatus::Selected, ApplicationStatus::InProgress], true)) {
            return;
        }

        if (! $this->covered($application, $entitlements)) {
            return;
        }

        $log = ReminderLog::query()->firstOrCreate(
            ['scholarship_application_id' => $application->id, 'days_before' => $this->daysBefore],
            ['sent_at' => now()],
        );

        if (! $log->wasRecentlyCreated) {
            return;
        }

        Mail::to($application->user)->send(new DeadlineReminderMail($application, $this->daysBefore));
    }

    /**
     * Whether the mentee's plan includes a reminder for this application.
     */
    private function covered(ScholarshipApplication $application, EntitlementServiceInterface $entitlements): bool
    {
        $limit = $entitlements->reminderLimit($application->user);

        if ($limit === null) {
            return true;
        }

        $allowed = $application->user->scholarshipApplications()
            ->orderByDesc('is_primary')->orderBy('id')->limit($limit)->pluck('id');

        return $allowed->contains($application->id);
    }
}
