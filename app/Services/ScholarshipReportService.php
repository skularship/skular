<?php

namespace App\Services;

use App\Contracts\ScholarshipReportServiceInterface;
use App\Enums\ReportedField;
use App\Enums\ReportStatus;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipInfoReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Stores "Informasi Tidak Akurat?" reports with server-side anti-spam limits
 * and records admin decisions on them.
 */
class ScholarshipReportService implements ScholarshipReportServiceInterface
{
    /**
     * Store a report after validating, sanitising and rate limiting it.
     *
     * @param  User  $user  The reporting mentee.
     * @param  Scholarship  $scholarship  A scholarship visible to mentees.
     * @param  array<string, mixed>  $input  Keys: field, suggested_value, source_url, note.
     * @return ScholarshipInfoReport The stored report with status "new".
     *
     * @throws ValidationException When the input is invalid, a limit is reached, or the scholarship is not visible.
     */
    public function submit(User $user, Scholarship $scholarship, array $input): ScholarshipInfoReport
    {
        if (! in_array($scholarship->status, ScholarshipStatus::publiclyVisible(), true)) {
            throw ValidationException::withMessages(['report' => __('Beasiswa ini tidak bisa dilaporkan.')]);
        }

        $data = $this->validated($this->sanitised($input));
        $field = ReportedField::from($data['field']);

        return DB::transaction(function () use ($user, $scholarship, $data, $field): ScholarshipInfoReport {
            // Serialise concurrent submissions of the same mentee so the daily
            // cap and the one-open-report rule cannot be raced past.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $recent = ScholarshipInfoReport::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($recent >= self::DAILY_LIMIT) {
                throw ValidationException::withMessages([
                    'report' => __('Kamu sudah mengirim :count laporan dalam 24 jam terakhir. Coba lagi besok ya.', ['count' => self::DAILY_LIMIT]),
                ]);
            }

            $has_open_report = ScholarshipInfoReport::query()
                ->where('user_id', $user->id)
                ->where('scholarship_id', $scholarship->id)
                ->where('field', $field->value)
                ->whereIn('status', array_map(fn (ReportStatus $status): string => $status->value, ReportStatus::openCases()))
                ->exists();

            if ($has_open_report) {
                throw ValidationException::withMessages([
                    'report' => __('Laporanmu untuk bagian ":field" masih kami cek. Terima kasih sudah sabar ya.', ['field' => $field->label()]),
                ]);
            }

            return ScholarshipInfoReport::query()->create([
                'user_id' => $user->id,
                'scholarship_id' => $scholarship->id,
                'field' => $field,
                'suggested_value' => $data['suggested_value'],
                'source_url' => $data['source_url'],
                'note' => $data['note'],
                'status' => ReportStatus::New,
            ]);
        });
    }

    /**
     * Record an admin decision on a report.
     *
     * @param  ScholarshipInfoReport  $report  The report under review.
     * @param  User  $admin  The deciding admin.
     * @param  ReportStatus  $status  New status (in review, accepted or rejected).
     * @param  string|null  $admin_note  Optional internal note.
     * @return ScholarshipInfoReport The updated report.
     */
    public function review(ScholarshipInfoReport $report, User $admin, ReportStatus $status, ?string $admin_note = null): ScholarshipInfoReport
    {
        $note = $this->plainText($admin_note);

        $report->forceFill([
            'status' => $status,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $note === '' ? $report->admin_note : mb_substr($note, 0, self::MAX_TEXT_LENGTH),
        ])->save();

        return $report;
    }

    /**
     * Validate sanitised input.
     *
     * @param  array<string, string|null>  $input
     * @return array{field: string, suggested_value: string, source_url: string|null, note: string|null}
     *
     * @throws ValidationException
     */
    private function validated(array $input): array
    {
        /** @var array{field: string, suggested_value: string, source_url: string|null, note: string|null} $data */
        $data = Validator::make($input, [
            'field' => ['required', Rule::enum(ReportedField::class)],
            'suggested_value' => ['required', 'string', 'min:3', 'max:'.self::MAX_TEXT_LENGTH],
            'source_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'note' => ['nullable', 'string', 'max:'.self::MAX_TEXT_LENGTH],
        ], [], [
            'field' => __('bagian yang tidak akurat'),
            'suggested_value' => __('informasi yang benar'),
            'source_url' => __('tautan bukti'),
            'note' => __('catatan'),
        ])->validate();

        return [
            'field' => $data['field'],
            'suggested_value' => $data['suggested_value'],
            'source_url' => $data['source_url'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }

    /**
     * Reduce every text input to trimmed plain text; empty optional values become null.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    private function sanitised(array $input): array
    {
        $clean = [];

        foreach (['field', 'suggested_value', 'source_url', 'note'] as $key) {
            $text = $this->plainText($input[$key] ?? null);
            $clean[$key] = $text === '' ? null : $text;
        }

        return $clean;
    }

    /**
     * Strip tags and control characters and collapse runs of blank space.
     */
    private function plainText(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = strip_tags((string) $value);
        $text = (string) preg_replace('/[^\P{C}\n]/u', '', $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }
}
