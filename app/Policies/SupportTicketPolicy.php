<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy extends BaseCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->currentCompanyId() !== null && ! $user->hasRole('Employee'));
    }

    public function view(User $user, mixed $model): bool
    {
        if (! $model instanceof SupportTicket) {
            return false;
        }

        return $user->isSuperAdmin()
            || (! $user->hasRole('Employee') && $user->canAccessCompany((int) $model->company_id))
            || ($model->employee_id && $user->employees()->whereKey($model->employee_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->currentCompanyId() !== null;
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->view($user, $model) && ! $user->hasRole('Employee');
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->isSuperAdmin();
    }
}
