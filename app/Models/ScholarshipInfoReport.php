<?php

namespace App\Models;

use App\Enums\ReportedField;
use App\Enums\ReportStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ScholarshipInfoReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A mentee's report that part of a scholarship detail page is inaccurate.
 *
 * Created through ScholarshipReportService (rate limited, plain text only)
 * and reviewed by admins in the Filament "Laporan Info Beasiswa" resource.
 * The source link is stored for admins and never shown to other mentees.
 *
 * @property int $id
 * @property int|null $user_id Reporter; null once the account is deleted.
 * @property int $scholarship_id
 * @property ReportedField $field
 * @property string $suggested_value What the mentee believes is correct.
 * @property string|null $source_url Evidence link (admin only).
 * @property string|null $note
 * @property ReportStatus $status
 * @property int|null $reviewed_by Admin who decided; null when undecided or the admin was deleted.
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $admin_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read Scholarship $scholarship
 * @property-read User|null $reviewer
 */
#[Fillable([
    'user_id',
    'scholarship_id',
    'field',
    'suggested_value',
    'source_url',
    'note',
    'status',
    'reviewed_by',
    'reviewed_at',
    'admin_note',
])]
class ScholarshipInfoReport extends Model
{
    /** @use HasFactory<ScholarshipInfoReportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field' => ReportedField::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the mentee who sent the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reported scholarship.
     *
     * @return BelongsTo<Scholarship, $this>
     */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    /**
     * Get the admin who reviewed the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
