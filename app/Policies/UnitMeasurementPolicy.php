<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UnitMeasurement;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitMeasurementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UnitMeasurement');
    }

    public function view(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('View:UnitMeasurement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UnitMeasurement');
    }

    public function update(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('Update:UnitMeasurement');
    }

    public function delete(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('Delete:UnitMeasurement');
    }

    public function restore(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('Restore:UnitMeasurement');
    }

    public function forceDelete(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('ForceDelete:UnitMeasurement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UnitMeasurement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UnitMeasurement');
    }

    public function replicate(AuthUser $authUser, UnitMeasurement $unitMeasurement): bool
    {
        return $authUser->can('Replicate:UnitMeasurement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UnitMeasurement');
    }

}