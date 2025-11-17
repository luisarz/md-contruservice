<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SmallCashBoxOperation;
use Illuminate\Auth\Access\HandlesAuthorization;

class SmallCashBoxOperationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SmallCashBoxOperation');
    }

    public function view(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('View:SmallCashBoxOperation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SmallCashBoxOperation');
    }

    public function update(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('Update:SmallCashBoxOperation');
    }

    public function delete(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('Delete:SmallCashBoxOperation');
    }

    public function restore(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('Restore:SmallCashBoxOperation');
    }

    public function forceDelete(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('ForceDelete:SmallCashBoxOperation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SmallCashBoxOperation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SmallCashBoxOperation');
    }

    public function replicate(AuthUser $authUser, SmallCashBoxOperation $smallCashBoxOperation): bool
    {
        return $authUser->can('Replicate:SmallCashBoxOperation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SmallCashBoxOperation');
    }

}