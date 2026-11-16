<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Shield policy for Subscription. Granting or changing Pro by hand is limited to trusted roles.
 * Each ability maps to a "{Action}:Subscription" permission seeded by RoleSeeder;
 * super_admin bypasses these checks (Gate::before in Shield).
 */
class SubscriptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin may list Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "ViewAny:Subscription" permission.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Subscription');
    }

    /**
     * Determine whether the admin may view one Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Subscription  $subscription  The record being authorized.
     * @return bool True when the admin holds the "View:Subscription" permission.
     */
    public function view(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->can('View:Subscription');
    }

    /**
     * Determine whether the admin may create Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "Create:Subscription" permission.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Subscription');
    }

    /**
     * Determine whether the admin may edit Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Subscription  $subscription  The record being authorized.
     * @return bool True when the admin holds the "Update:Subscription" permission.
     */
    public function update(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->can('Update:Subscription');
    }

    /**
     * Determine whether the admin may delete one Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Subscription  $subscription  The record being authorized.
     * @return bool True when the admin holds the "Delete:Subscription" permission.
     */
    public function delete(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->can('Delete:Subscription');
    }

    /**
     * Determine whether the admin may bulk delete Subscription records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "DeleteAny:Subscription" permission.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Subscription');
    }
}
