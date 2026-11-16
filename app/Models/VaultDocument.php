<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private document a Pro mentee keeps for applications (stored on the local private disk).
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size_kb
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'title', 'path', 'original_name', 'mime', 'size_kb'])]
class VaultDocument extends Model
{
    /**
     * Get the owner of the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
