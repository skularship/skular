<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Shield policy for Order. Orders are a financial record: seeded read-only, never create or delete from the panel.
 * Each ability maps to a "{Action}:Order" permission seeded by RoleSeeder;
 * super_admin bypasses these checks (Gate::before in Shield).
 */
class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin may list Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "ViewAny:Order" permission.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Order');
    }

    /**
     * Determine whether the admin may view one Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Order  $order  The record being authorized.
     * @return bool True when the admin holds the "View:Order" permission.
     */
    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('View:Order');
    }

    /**
     * Determine whether the admin may create Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "Create:Order" permission.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Order');
    }

    /**
     * Determine whether the admin may edit Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Order  $order  The record being authorized.
     * @return bool True when the admin holds the "Update:Order" permission.
     */
    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Update:Order');
    }

    /**
     * Determine whether the admin may delete one Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Order  $order  The record being authorized.
     * @return bool True when the admin holds the "Delete:Order" permission.
     */
    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Delete:Order');
    }

    /**
     * Determine whether the admin may bulk delete Order records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "DeleteAny:Order" permission.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Order');
    }
}
