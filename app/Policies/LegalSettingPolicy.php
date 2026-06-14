<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCompanyAccess;

class LegalSettingPolicy
{
    use ChecksCompanyAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Company Owner', 'Payroll Manager', 'Accountant']);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Company Owner', 'Payroll Manager']);
    }

    public function update(User $user): bool
    {
        return $this->create($user);
    }

    public function delete(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
