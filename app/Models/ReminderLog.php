<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Proof that a deadline reminder was sent, so it is never sent twice.
 *
 * @property int $id
 * @property int $scholarship_application_id
 * @property int $days_before
 * @property CarbonImmutable $sent_at
 */
#[Fillable(['scholarship_application_id', 'days_before', 'sent_at'])]
class ReminderLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sent_at' => 'immutable_datetime'];
    }
}
