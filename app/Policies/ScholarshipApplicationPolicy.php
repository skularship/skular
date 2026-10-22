<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScholarshipApplication;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ScholarshipApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ScholarshipApplication');
    }

    public function view(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('View:ScholarshipApplication');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ScholarshipApplication');
    }

    public function update(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('Update:ScholarshipApplication');
    }

    public function delete(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('Delete:ScholarshipApplication');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ScholarshipApplication');
    }

    public function restore(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('Restore:ScholarshipApplication');
    }

    public function forceDelete(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('ForceDelete:ScholarshipApplication');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ScholarshipApplication');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ScholarshipApplication');
    }

    public function replicate(AuthUser $authUser, ScholarshipApplication $scholarshipApplication): bool
    {
        return $authUser->can('Replicate:ScholarshipApplication');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ScholarshipApplication');
    }
}
