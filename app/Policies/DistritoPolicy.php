<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Distrito;
use Illuminate\Auth\Access\HandlesAuthorization;

class DistritoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Distrito');
    }

    public function view(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('View:Distrito');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Distrito');
    }

    public function update(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('Update:Distrito');
    }

    public function delete(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('Delete:Distrito');
    }

    public function restore(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('Restore:Distrito');
    }

    public function forceDelete(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('ForceDelete:Distrito');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Distrito');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Distrito');
    }

    public function replicate(AuthUser $authUser, Distrito $distrito): bool
    {
        return $authUser->can('Replicate:Distrito');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Distrito');
    }

}