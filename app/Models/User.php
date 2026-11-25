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
use Illuminate\Database\Eloquent\Collection;
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
 * @property array<int, string>|null $tags Admin labels such as "alumni_akademi_unggulan" (invitation-only events, feedback segments).
 * @property Carbon|null $feedback_commitment_at When the mentee agreed to give honest feedback at the end of every module (required before opening modules).
 * @property array<array-key, mixed>|null $onboarding_tours Raw JSON of finished or skipped product tours, {tour key: ISO timestamp}; read through ProductTourService.
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cluster|null $cluster
 * @property-read Collection<int, ScholarshipApplication> $scholarshipApplications
 * @property-read Collection<int, ScholarshipInfoReport> $scholarshipInfoReports
 * @property-read Collection<int, SupportTicket> $supportTickets
 * @property-read Collection<int, Scholarship> $scholarships
 * @property-read ScholarshipApplication|null $primaryApplication
 * @property-read Collection<int, Checkpoint> $checkpoints
 * @property-read Collection<int, UserPracticeResponse> $practiceResponses
 * @property-read Collection<int, UserBrainstormingSession> $brainstormingSessions
 * @property-read Collection<int, ModuleFeedback> $moduleFeedbacks
 * @property-read Subscription|null $proSubscription
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, UserConsent> $consents
 * @property-read Collection<int, EventRegistration> $eventRegistrations
 * @property-read Collection<int, TryoutAttempt> $tryoutAttempts
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
    'tags',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the mentee's Pro subscription row, if they ever bought Pro.
     *
     * @return HasOne<Subscription, $this>
     */
    public function proSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the Premium orders the mentee has placed.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the legal documents the mentee accepted (consent log).
     *
     * @return HasMany<UserConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    /**
     * Get the mentee's own referral code, if it was ever created.
     *
     * @return HasOne<ReferralCode, $this>
     */
    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    /**
     * Get the documents the mentee keeps in the vault.
     *
     * @return HasMany<VaultDocument, $this>
     */
    public function vaultDocuments(): HasMany
    {
        return $this->hasMany(VaultDocument::class);
    }

    /**
     * Determine whether the mentee has an active Pro plan right now.
     *
     * The single entry point every paid feature should call.
     */
    public function hasPro(): bool
    {
        $subscription = $this->proSubscription;

        return $subscription !== null && $subscription->active_until->isFuture();
    }

    /**
     * Determine whether the mentee may access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Read the loaded relation so the sidebar (rendered on every page) and
        // Filament share one query per request. Spatie unsets this relation
        // whenever roles are assigned or removed, so it never goes stale.
        return $this->loadMissing('roles')
            ->roles
            ->whereIn('name', AdminRole::values())
            ->isNotEmpty();
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
            'feedback_commitment_at' => 'datetime',
            'tags' => 'array',
            'onboarding_tours' => 'array',
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
     * Determine whether the mentee agreed to the free-access feedback commitment.
     */
    public function hasFeedbackCommitment(): bool
    {
        return $this->feedback_commitment_at !== null;
    }

    /**
     * Determine whether an admin gave the mentee this tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    /**
     * Get the mentee's event registrations.
     *
     * @return HasMany<EventRegistration, $this>
     */
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get the mentee's Try Out attempts.
     *
     * @return HasMany<TryoutAttempt, $this>
     */
    public function tryoutAttempts(): HasMany
    {
        return $this->hasMany(TryoutAttempt::class);
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
     * Get the "Informasi Tidak Akurat?" reports the mentee sent.
     *
     * @return HasMany<ScholarshipInfoReport, $this>
     */
    public function scholarshipInfoReports(): HasMany
    {
        return $this->hasMany(ScholarshipInfoReport::class);
    }

    /**
     * Get the help requests and bug reports the mentee sent from "Bantuan".
     *
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
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
