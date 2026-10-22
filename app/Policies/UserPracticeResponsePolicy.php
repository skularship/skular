<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UserPracticeResponse;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPracticeResponsePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserPracticeResponse');
    }

    public function view(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('View:UserPracticeResponse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UserPracticeResponse');
    }

    public function update(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('Update:UserPracticeResponse');
    }

    public function delete(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('Delete:UserPracticeResponse');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UserPracticeResponse');
    }

    public function restore(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('Restore:UserPracticeResponse');
    }

    public function forceDelete(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('ForceDelete:UserPracticeResponse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UserPracticeResponse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UserPracticeResponse');
    }

    public function replicate(AuthUser $authUser, UserPracticeResponse $userPracticeResponse): bool
    {
        return $authUser->can('Replicate:UserPracticeResponse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UserPracticeResponse');
    }
}
