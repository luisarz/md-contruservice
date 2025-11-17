<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PersonType;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PersonType');
    }

    public function view(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('View:PersonType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PersonType');
    }

    public function update(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('Update:PersonType');
    }

    public function delete(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('Delete:PersonType');
    }

    public function restore(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('Restore:PersonType');
    }

    public function forceDelete(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('ForceDelete:PersonType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PersonType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PersonType');
    }

    public function replicate(AuthUser $authUser, PersonType $personType): bool
    {
        return $authUser->can('Replicate:PersonType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PersonType');
    }

}