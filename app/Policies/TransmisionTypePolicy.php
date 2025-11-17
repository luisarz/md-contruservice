<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TransmisionType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransmisionTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransmisionType');
    }

    public function view(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('View:TransmisionType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransmisionType');
    }

    public function update(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('Update:TransmisionType');
    }

    public function delete(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('Delete:TransmisionType');
    }

    public function restore(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('Restore:TransmisionType');
    }

    public function forceDelete(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('ForceDelete:TransmisionType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransmisionType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransmisionType');
    }

    public function replicate(AuthUser $authUser, TransmisionType $transmisionType): bool
    {
        return $authUser->can('Replicate:TransmisionType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransmisionType');
    }

}