<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Services\AccessScope;

class AssetPolicy
{
    public function __construct(private AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->scope->assets($user)->whereKey($asset->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(AccessScope::COORDINATORS);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->scope->coordinate($user, $asset);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return false;
    }
}
