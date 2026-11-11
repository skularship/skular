<?php

namespace App\Contracts;

use App\Enums\ReportStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipInfoReport;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Receives mentee reports of inaccurate scholarship data ("Informasi Tidak
 * Akurat?") and records the admin decision on them.
 */
interface ScholarshipReportServiceInterface
{
    /**
     * Most reports one mentee may send within 24 hours.
     */
    public const DAILY_LIMIT = 5;

    /**
     * Longest suggested value or note, in characters.
     */
    public const MAX_TEXT_LENGTH = 1000;

    /**
     * Store a report after validating, sanitising and rate limiting it.
     *
     * Enforced server-side: at most DAILY_LIMIT reports per mentee per 24
     * hours, and one open report per mentee per scholarship per field.
     * Text is stored as plain text (tags stripped, whitespace collapsed).
     *
     * @param  User  $user  The reporting mentee.
     * @param  Scholarship  $scholarship  A scholarship visible to mentees.
     * @param  array<string, mixed>  $input  Keys: field, suggested_value, source_url, note.
     * @return ScholarshipInfoReport The stored report with status "new".
     *
     * @throws ValidationException When the input is invalid, a limit is reached, or the scholarship is not visible.
     */
    public function submit(User $user, Scholarship $scholarship, array $input): ScholarshipInfoReport;

    /**
     * Record an admin decision on a report.
     *
     * Only changes the report; the scholarship data itself is edited by the
     * admin by hand so unverified suggestions never reach mentees.
     *
     * @param  ScholarshipInfoReport  $report  The report under review.
     * @param  User  $admin  The deciding admin.
     * @param  ReportStatus  $status  New status (in review, accepted or rejected).
     * @param  string|null  $admin_note  Optional internal note.
     * @return ScholarshipInfoReport The updated report.
     */
    public function review(ScholarshipInfoReport $report, User $admin, ReportStatus $status, ?string $admin_note = null): ScholarshipInfoReport;
}
