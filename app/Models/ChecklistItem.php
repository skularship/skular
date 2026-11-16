<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item of the checklist a mentee keeps for a chosen scholarship.
 *
 * @property int $id
 * @property int $scholarship_application_id
 * @property string $label
 * @property bool $is_done
 * @property int|null $vault_document_id
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ScholarshipApplication $application
 * @property-read VaultDocument|null $document
 */
#[Fillable(['scholarship_application_id', 'label', 'is_done', 'vault_document_id', 'position'])]
class ChecklistItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }

    /**
     * Get the application this item belongs to.
     *
     * @return BelongsTo<ScholarshipApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id');
    }

    /**
     * Get the vault document attached to this item, if any.
     *
     * @return BelongsTo<VaultDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(VaultDocument::class, 'vault_document_id');
    }
}
