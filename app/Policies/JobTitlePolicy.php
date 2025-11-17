<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JobTitle;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobTitlePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobTitle');
    }

    public function view(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('View:JobTitle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobTitle');
    }

    public function update(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('Update:JobTitle');
    }

    public function delete(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('Delete:JobTitle');
    }

    public function restore(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('Restore:JobTitle');
    }

    public function forceDelete(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('ForceDelete:JobTitle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobTitle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobTitle');
    }

    public function replicate(AuthUser $authUser, JobTitle $jobTitle): bool
    {
        return $authUser->can('Replicate:JobTitle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobTitle');
    }

}