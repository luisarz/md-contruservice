<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DteTransmisionWherehouse;
use Illuminate\Auth\Access\HandlesAuthorization;

class DteTransmisionWherehousePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DteTransmisionWherehouse');
    }

    public function view(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('View:DteTransmisionWherehouse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DteTransmisionWherehouse');
    }

    public function update(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('Update:DteTransmisionWherehouse');
    }

    public function delete(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('Delete:DteTransmisionWherehouse');
    }

    public function restore(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('Restore:DteTransmisionWherehouse');
    }

    public function forceDelete(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('ForceDelete:DteTransmisionWherehouse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DteTransmisionWherehouse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DteTransmisionWherehouse');
    }

    public function replicate(AuthUser $authUser, DteTransmisionWherehouse $dteTransmisionWherehouse): bool
    {
        return $authUser->can('Replicate:DteTransmisionWherehouse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DteTransmisionWherehouse');
    }

}