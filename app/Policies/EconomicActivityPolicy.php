<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EconomicActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class EconomicActivityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EconomicActivity');
    }

    public function view(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('View:EconomicActivity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EconomicActivity');
    }

    public function update(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('Update:EconomicActivity');
    }

    public function delete(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('Delete:EconomicActivity');
    }

    public function restore(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('Restore:EconomicActivity');
    }

    public function forceDelete(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('ForceDelete:EconomicActivity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EconomicActivity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EconomicActivity');
    }

    public function replicate(AuthUser $authUser, EconomicActivity $economicActivity): bool
    {
        return $authUser->can('Replicate:EconomicActivity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EconomicActivity');
    }

}