<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ClusterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $label
 * @property string|null $description
 * @property string|null $icon
 * @property int $ordering
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'label', 'description', 'icon', 'ordering', 'is_active'])]
class Cluster extends Model
{
    /** @use HasFactory<ClusterFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordering' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope the query to active clusters in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activeOrdered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('ordering')->orderBy('label');
    }

    /**
     * Get the mentees interested in this cluster.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
