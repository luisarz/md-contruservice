<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tribute;
use Illuminate\Auth\Access\HandlesAuthorization;

class TributePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tribute');
    }

    public function view(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('View:Tribute');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tribute');
    }

    public function update(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('Update:Tribute');
    }

    public function delete(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('Delete:Tribute');
    }

    public function restore(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('Restore:Tribute');
    }

    public function forceDelete(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('ForceDelete:Tribute');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tribute');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tribute');
    }

    public function replicate(AuthUser $authUser, Tribute $tribute): bool
    {
        return $authUser->can('Replicate:Tribute');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tribute');
    }

}