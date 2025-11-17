<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DestinationEnviroment;
use Illuminate\Auth\Access\HandlesAuthorization;

class DestinationEnviromentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DestinationEnviroment');
    }

    public function view(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('View:DestinationEnviroment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DestinationEnviroment');
    }

    public function update(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('Update:DestinationEnviroment');
    }

    public function delete(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('Delete:DestinationEnviroment');
    }

    public function restore(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('Restore:DestinationEnviroment');
    }

    public function forceDelete(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('ForceDelete:DestinationEnviroment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DestinationEnviroment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DestinationEnviroment');
    }

    public function replicate(AuthUser $authUser, DestinationEnviroment $destinationEnviroment): bool
    {
        return $authUser->can('Replicate:DestinationEnviroment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DestinationEnviroment');
    }

}