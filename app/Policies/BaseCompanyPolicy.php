<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCompanyAccess;

class BaseCompanyPolicy
{
    use ChecksCompanyAccess;

    public function viewAny(User $user): bool
    {
        return $user->currentCompanyId() !== null;
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->belongsToUserCompany($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->currentCompanyId() !== null;
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->belongsToUserCompany($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->belongsToUserCompany($user, $model);
    }
}
