<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Shield policy for Product. Prices are sensitive, so only trusted roles get Update.
 * Each ability maps to a "{Action}:Product" permission seeded by RoleSeeder;
 * super_admin bypasses these checks (Gate::before in Shield).
 */
class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin may list Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "ViewAny:Product" permission.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Product');
    }

    /**
     * Determine whether the admin may view one Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Product  $product  The record being authorized.
     * @return bool True when the admin holds the "View:Product" permission.
     */
    public function view(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('View:Product');
    }

    /**
     * Determine whether the admin may create Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "Create:Product" permission.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Product');
    }

    /**
     * Determine whether the admin may edit Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Product  $product  The record being authorized.
     * @return bool True when the admin holds the "Update:Product" permission.
     */
    public function update(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Update:Product');
    }

    /**
     * Determine whether the admin may delete one Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @param  Product  $product  The record being authorized.
     * @return bool True when the admin holds the "Delete:Product" permission.
     */
    public function delete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Delete:Product');
    }

    /**
     * Determine whether the admin may bulk delete Product records.
     *
     * @param  AuthUser  $authUser  The signed-in admin.
     * @return bool True when the admin holds the "DeleteAny:Product" permission.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Product');
    }
}
