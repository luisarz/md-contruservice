<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Contingency;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContingencyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contingency');
    }

    public function view(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('View:Contingency');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contingency');
    }

    public function update(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('Update:Contingency');
    }

    public function delete(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('Delete:Contingency');
    }

    public function restore(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('Restore:Contingency');
    }

    public function forceDelete(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('ForceDelete:Contingency');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Contingency');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Contingency');
    }

    public function replicate(AuthUser $authUser, Contingency $contingency): bool
    {
        return $authUser->can('Replicate:Contingency');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Contingency');
    }

}