<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CustomerDocumentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerDocumentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerDocumentType');
    }

    public function view(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('View:CustomerDocumentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerDocumentType');
    }

    public function update(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('Update:CustomerDocumentType');
    }

    public function delete(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('Delete:CustomerDocumentType');
    }

    public function restore(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('Restore:CustomerDocumentType');
    }

    public function forceDelete(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('ForceDelete:CustomerDocumentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerDocumentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerDocumentType');
    }

    public function replicate(AuthUser $authUser, CustomerDocumentType $customerDocumentType): bool
    {
        return $authUser->can('Replicate:CustomerDocumentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerDocumentType');
    }

}