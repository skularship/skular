<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuickQuestion;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QuickQuestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuickQuestion');
    }

    public function view(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('View:QuickQuestion');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuickQuestion');
    }

    public function update(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('Update:QuickQuestion');
    }

    public function delete(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('Delete:QuickQuestion');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QuickQuestion');
    }

    public function restore(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('Restore:QuickQuestion');
    }

    public function forceDelete(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('ForceDelete:QuickQuestion');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuickQuestion');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuickQuestion');
    }

    public function replicate(AuthUser $authUser, QuickQuestion $quickQuestion): bool
    {
        return $authUser->can('Replicate:QuickQuestion');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuickQuestion');
    }
}
