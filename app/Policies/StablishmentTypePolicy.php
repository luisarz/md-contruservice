<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StablishmentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class StablishmentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StablishmentType');
    }

    public function view(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('View:StablishmentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StablishmentType');
    }

    public function update(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('Update:StablishmentType');
    }

    public function delete(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('Delete:StablishmentType');
    }

    public function restore(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('Restore:StablishmentType');
    }

    public function forceDelete(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('ForceDelete:StablishmentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StablishmentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StablishmentType');
    }

    public function replicate(AuthUser $authUser, StablishmentType $stablishmentType): bool
    {
        return $authUser->can('Replicate:StablishmentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StablishmentType');
    }

}