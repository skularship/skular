<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ModuleFeedback;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModuleFeedbackPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ModuleFeedback');
    }

    public function view(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('View:ModuleFeedback');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ModuleFeedback');
    }

    public function update(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('Update:ModuleFeedback');
    }

    public function delete(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('Delete:ModuleFeedback');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ModuleFeedback');
    }

    public function restore(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('Restore:ModuleFeedback');
    }

    public function forceDelete(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('ForceDelete:ModuleFeedback');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ModuleFeedback');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ModuleFeedback');
    }

    public function replicate(AuthUser $authUser, ModuleFeedback $moduleFeedback): bool
    {
        return $authUser->can('Replicate:ModuleFeedback');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ModuleFeedback');
    }
}
