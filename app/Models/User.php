<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $google_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $avatar_url
 * @property EducationLevel|null $level
 * @property int|null $age
 * @property string|null $gpa
 * @property bool|null $has_sktm
 * @property EducationLevel|null $desired_level
 * @property LocationScope|null $desired_location
 * @property int|null $cluster_id
 * @property string|null $phone
 * @property string|null $subscription
 * @property Carbon|null $profile_completed_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cluster|null $cluster
 */
#[Fillable([
    'name',
    'email',
    'email_verified_at',
    'password',
    'google_id',
    'avatar_url',
    'level',
    'age',
    'gpa',
    'has_sktm',
    'desired_level',
    'desired_location',
    'cluster_id',
    'phone',
    'subscription',
    'profile_completed_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Determine whether the mentee may access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roles()->whereIn('name', AdminRole::values())->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'level' => EducationLevel::class,
            'desired_level' => EducationLevel::class,
            'desired_location' => LocationScope::class,
            'gpa' => 'decimal:2',
            'has_sktm' => 'boolean',
            'age' => 'integer',
            'profile_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Determine whether the mentee has finished the profiling wizard.
     */
    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed_at !== null;
    }

    /**
     * Get the cluster this mentee is interested in.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * Get the scholarship applications belonging to the mentee.
     *
     * @return HasMany<ScholarshipApplication, $this>
     */
    public function scholarshipApplications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    /**
     * Get the scholarship applications with their scholarship attached.
     *
     * @return BelongsToMany<Scholarship, $this>
     */
    public function scholarships(): BelongsToMany
    {
        return $this->belongsToMany(Scholarship::class, 'scholarship_applications')
            ->withPivot(['id', 'status', 'is_primary', 'selected_at', 'submitted_at', 'submission_proof', 'result'])
            ->withTimestamps();
    }

    /**
     * Get the application selected as the mentee's primary scholarship.
     *
     * @return HasOne<ScholarshipApplication, $this>
     */
    public function primaryApplication(): HasOne
    {
        return $this->hasOne(ScholarshipApplication::class)->where('is_primary', true);
    }

    /**
     * Get the learning checkpoints belonging to the mentee.
     *
     * @return HasMany<Checkpoint, $this>
     */
    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class);
    }

    /**
     * Get the practice submissions belonging to the mentee.
     *
     * @return HasMany<UserPracticeResponse, $this>
     */
    public function practiceResponses(): HasMany
    {
        return $this->hasMany(UserPracticeResponse::class);
    }

    /**
     * Get the brainstorming sessions belonging to the mentee.
     *
     * @return HasMany<UserBrainstormingSession, $this>
     */
    public function brainstormingSessions(): HasMany
    {
        return $this->hasMany(UserBrainstormingSession::class);
    }

    /**
     * Get the module feedback submitted by the mentee.
     *
     * @return HasMany<ModuleFeedback, $this>
     */
    public function moduleFeedbacks(): HasMany
    {
        return $this->hasMany(ModuleFeedback::class);
    }
}
