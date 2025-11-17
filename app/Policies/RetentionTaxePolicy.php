<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RetentionTaxe;
use Illuminate\Auth\Access\HandlesAuthorization;

class RetentionTaxePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetentionTaxe');
    }

    public function view(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('View:RetentionTaxe');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetentionTaxe');
    }

    public function update(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('Update:RetentionTaxe');
    }

    public function delete(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('Delete:RetentionTaxe');
    }

    public function restore(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('Restore:RetentionTaxe');
    }

    public function forceDelete(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('ForceDelete:RetentionTaxe');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetentionTaxe');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetentionTaxe');
    }

    public function replicate(AuthUser $authUser, RetentionTaxe $retentionTaxe): bool
    {
        return $authUser->can('Replicate:RetentionTaxe');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetentionTaxe');
    }

}