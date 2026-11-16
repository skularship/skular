<?php

namespace App\Models;

use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use Carbon\CarbonImmutable;
use Database\Factories\UserConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One accepted legal document version (consent log). Written only through
 * ConsentServiceInterface; rows are never updated, a new version adds a row.
 *
 * @property int $id
 * @property int $user_id
 * @property LegalDocument $document
 * @property string $version
 * @property ConsentSource $source
 * @property bool|null $age_confirmed Whether the mentee stated they are 18+ or have parent/guardian permission (null when not asked).
 * @property CarbonImmutable $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'document', 'version', 'source', 'age_confirmed', 'accepted_at'])]
class UserConsent extends Model
{
    /** @use HasFactory<UserConsentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document' => LegalDocument::class,
            'source' => ConsentSource::class,
            'age_confirmed' => 'boolean',
            'accepted_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the mentee who gave the consent.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
