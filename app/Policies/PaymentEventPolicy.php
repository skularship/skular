<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentEvent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Shield policy for PaymentEvent. Raw payment audit log: read, and Update lets an admin queue an event again.
 * Each ability maps to a "{Action}:PaymentEvent" permission seeded by RoleSeeder;
 * super_admin bypasses these checks (Gate::before in Shield).
 */
class PaymentEventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin may list PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "ViewAny:PaymentEvent" permission.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentEvent');
    }

    /**
     * Determine whether the admin may view one PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  PaymentEvent  $paymentEvent  The record being authorized.
     * @return bool True when the admin holds the "View:PaymentEvent" permission.
     */
    public function view(AuthUser $authUser, PaymentEvent $paymentEvent): bool
    {
        return $authUser->can('View:PaymentEvent');
    }

    /**
     * Determine whether the admin may create PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "Create:PaymentEvent" permission.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentEvent');
    }

    /**
     * Determine whether the admin may edit PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  PaymentEvent  $paymentEvent  The record being authorized.
     * @return bool True when the admin holds the "Update:PaymentEvent" permission.
     */
    public function update(AuthUser $authUser, PaymentEvent $paymentEvent): bool
    {
        return $authUser->can('Update:PaymentEvent');
    }

    /**
     * Determine whether the admin may delete one PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  PaymentEvent  $paymentEvent  The record being authorized.
     * @return bool True when the admin holds the "Delete:PaymentEvent" permission.
     */
    public function delete(AuthUser $authUser, PaymentEvent $paymentEvent): bool
    {
        return $authUser->can('Delete:PaymentEvent');
    }

    /**
     * Determine whether the admin may bulk delete PaymentEvent records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "DeleteAny:PaymentEvent" permission.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PaymentEvent');
    }
}
