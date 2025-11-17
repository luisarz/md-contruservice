<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kardex;
use Illuminate\Auth\Access\HandlesAuthorization;

class KardexPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kardex');
    }

    public function view(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('View:Kardex');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kardex');
    }

    public function update(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('Update:Kardex');
    }

    public function delete(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('Delete:Kardex');
    }

    public function restore(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('Restore:Kardex');
    }

    public function forceDelete(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('ForceDelete:Kardex');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kardex');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kardex');
    }

    public function replicate(AuthUser $authUser, Kardex $kardex): bool
    {
        return $authUser->can('Replicate:Kardex');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kardex');
    }

}