<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashBoxOpen;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashBoxOpenPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashBoxOpen');
    }

    public function view(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('View:CashBoxOpen');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashBoxOpen');
    }

    public function update(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('Update:CashBoxOpen');
    }

    public function delete(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('Delete:CashBoxOpen');
    }

    public function restore(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('Restore:CashBoxOpen');
    }

    public function forceDelete(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('ForceDelete:CashBoxOpen');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashBoxOpen');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashBoxOpen');
    }

    public function replicate(AuthUser $authUser, CashBoxOpen $cashBoxOpen): bool
    {
        return $authUser->can('Replicate:CashBoxOpen');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashBoxOpen');
    }

}