<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Fase');
    }

    public function view(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('View:Fase');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Fase');
    }

    public function update(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('Update:Fase');
    }

    public function delete(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('Delete:Fase');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Fase');
    }

    public function restore(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('Restore:Fase');
    }

    public function forceDelete(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('ForceDelete:Fase');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Fase');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Fase');
    }

    public function replicate(AuthUser $authUser, Fase $fase): bool
    {
        return $authUser->can('Replicate:Fase');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Fase');
    }
}
