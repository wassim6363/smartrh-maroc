<?php

namespace App\Policies;

use App\Models\User;

class ContractTemplatePolicy extends BaseCompanyPolicy
{
    public function view(User $user, mixed $model): bool
    {
        return $model->company_id === null || $this->belongsToUserCompany($user, $model);
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
