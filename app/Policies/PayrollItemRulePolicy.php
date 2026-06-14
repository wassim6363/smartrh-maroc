<?php

namespace App\Policies;

use App\Models\PayrollItemRule;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyAccess;

class PayrollItemRulePolicy
{
    use ChecksCompanyAccess;

    public function view(User $user, PayrollItemRule $rule): bool
    {
        return $rule->payrollItem && $this->belongsToUserCompany($user, $rule->payrollItem);
    }

    public function update(User $user, PayrollItemRule $rule): bool
    {
        return $this->view($user, $rule);
    }
}
