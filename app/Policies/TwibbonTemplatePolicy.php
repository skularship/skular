<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TwibbonTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TwibbonTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TwibbonTemplate');
    }

    public function view(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('View:TwibbonTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TwibbonTemplate');
    }

    public function update(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('Update:TwibbonTemplate');
    }

    public function delete(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('Delete:TwibbonTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TwibbonTemplate');
    }

    public function restore(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('Restore:TwibbonTemplate');
    }

    public function forceDelete(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('ForceDelete:TwibbonTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TwibbonTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TwibbonTemplate');
    }

    public function replicate(AuthUser $authUser, TwibbonTemplate $twibbonTemplate): bool
    {
        return $authUser->can('Replicate:TwibbonTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TwibbonTemplate');
    }
}
