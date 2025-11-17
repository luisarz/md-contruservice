<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AdjustmentInventory;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdjustmentInventoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdjustmentInventory');
    }

    public function view(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('View:AdjustmentInventory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdjustmentInventory');
    }

    public function update(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('Update:AdjustmentInventory');
    }

    public function delete(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('Delete:AdjustmentInventory');
    }

    public function restore(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('Restore:AdjustmentInventory');
    }

    public function forceDelete(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('ForceDelete:AdjustmentInventory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdjustmentInventory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdjustmentInventory');
    }

    public function replicate(AuthUser $authUser, AdjustmentInventory $adjustmentInventory): bool
    {
        return $authUser->can('Replicate:AdjustmentInventory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdjustmentInventory');
    }

}