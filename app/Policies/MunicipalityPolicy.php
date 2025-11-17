<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Municipality;
use Illuminate\Auth\Access\HandlesAuthorization;

class MunicipalityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Municipality');
    }

    public function view(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('View:Municipality');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Municipality');
    }

    public function update(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('Update:Municipality');
    }

    public function delete(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('Delete:Municipality');
    }

    public function restore(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('Restore:Municipality');
    }

    public function forceDelete(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('ForceDelete:Municipality');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Municipality');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Municipality');
    }

    public function replicate(AuthUser $authUser, Municipality $municipality): bool
    {
        return $authUser->can('Replicate:Municipality');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Municipality');
    }

}