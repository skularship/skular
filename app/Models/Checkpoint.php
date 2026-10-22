<?php

namespace App\Models;

use App\Enums\CheckpointStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CheckpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property int $fase_id
 * @property CheckpointStatus $status
 * @property int|null $score
 * @property int $attempts
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $available_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'user_id',
    'module_id',
    'fase_id',
    'status',
    'score',
    'attempts',
    'metadata',
    'available_at',
    'started_at',
    'completed_at',
])]
class Checkpoint extends Model
{
    /** @use HasFactory<CheckpointFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckpointStatus::class,
            'score' => 'integer',
            'attempts' => 'integer',
            'metadata' => 'array',
            'available_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Determine whether the checkpoint is finished.
     */
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    /**
     * Get the mentee this checkpoint belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module this checkpoint belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the fase this checkpoint belongs to.
     *
     * @return BelongsTo<Fase, $this>
     */
    public function fase(): BelongsTo
    {
        return $this->belongsTo(Fase::class);
    }
}
