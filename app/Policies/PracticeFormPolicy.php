<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PracticeForm;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PracticeFormPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PracticeForm');
    }

    public function view(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('View:PracticeForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PracticeForm');
    }

    public function update(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('Update:PracticeForm');
    }

    public function delete(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('Delete:PracticeForm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PracticeForm');
    }

    public function restore(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('Restore:PracticeForm');
    }

    public function forceDelete(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('ForceDelete:PracticeForm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PracticeForm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PracticeForm');
    }

    public function replicate(AuthUser $authUser, PracticeForm $practiceForm): bool
    {
        return $authUser->can('Replicate:PracticeForm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PracticeForm');
    }
}
