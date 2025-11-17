<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OperationCondition;
use Illuminate\Auth\Access\HandlesAuthorization;

class OperationConditionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OperationCondition');
    }

    public function view(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('View:OperationCondition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OperationCondition');
    }

    public function update(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('Update:OperationCondition');
    }

    public function delete(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('Delete:OperationCondition');
    }

    public function restore(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('Restore:OperationCondition');
    }

    public function forceDelete(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('ForceDelete:OperationCondition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OperationCondition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OperationCondition');
    }

    public function replicate(AuthUser $authUser, OperationCondition $operationCondition): bool
    {
        return $authUser->can('Replicate:OperationCondition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OperationCondition');
    }

}