<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksCompanyAccess
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    protected function belongsToUserCompany(User $user, mixed $model): bool
    {
        if (method_exists($model, 'getAttribute')) {
            $companyId = $model->getAttribute('company_id');

            if ($companyId) {
                return $user->canAccessCompany((int) $companyId);
            }

            if ($model->getTable() === 'companies') {
                return $user->canAccessCompany((int) $model->getKey());
            }
        }

        return false;
    }
}
