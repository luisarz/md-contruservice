<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BillingModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingModelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BillingModel');
    }

    public function view(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('View:BillingModel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BillingModel');
    }

    public function update(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('Update:BillingModel');
    }

    public function delete(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('Delete:BillingModel');
    }

    public function restore(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('Restore:BillingModel');
    }

    public function forceDelete(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('ForceDelete:BillingModel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BillingModel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BillingModel');
    }

    public function replicate(AuthUser $authUser, BillingModel $billingModel): bool
    {
        return $authUser->can('Replicate:BillingModel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BillingModel');
    }

}