<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContingencyType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContingencyTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContingencyType');
    }

    public function view(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('View:ContingencyType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContingencyType');
    }

    public function update(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('Update:ContingencyType');
    }

    public function delete(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('Delete:ContingencyType');
    }

    public function restore(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('Restore:ContingencyType');
    }

    public function forceDelete(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('ForceDelete:ContingencyType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContingencyType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContingencyType');
    }

    public function replicate(AuthUser $authUser, ContingencyType $contingencyType): bool
    {
        return $authUser->can('Replicate:ContingencyType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContingencyType');
    }

}