<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Checkpoint;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CheckpointPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Checkpoint');
    }

    public function view(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('View:Checkpoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Checkpoint');
    }

    public function update(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('Update:Checkpoint');
    }

    public function delete(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('Delete:Checkpoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Checkpoint');
    }

    public function restore(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('Restore:Checkpoint');
    }

    public function forceDelete(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('ForceDelete:Checkpoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Checkpoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Checkpoint');
    }

    public function replicate(AuthUser $authUser, Checkpoint $checkpoint): bool
    {
        return $authUser->can('Replicate:Checkpoint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Checkpoint');
    }
}
