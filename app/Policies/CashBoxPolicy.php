<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashBox;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashBoxPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashBox');
    }

    public function view(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('View:CashBox');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashBox');
    }

    public function update(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Update:CashBox');
    }

    public function delete(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Delete:CashBox');
    }

    public function restore(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Restore:CashBox');
    }

    public function forceDelete(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('ForceDelete:CashBox');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashBox');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashBox');
    }

    public function replicate(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Replicate:CashBox');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashBox');
    }

}