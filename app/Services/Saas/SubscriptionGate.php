<?php

namespace App\Services\Saas;

use App\Models\Company;

class SubscriptionGate
{
    public function assertCanAddEmployee(Company $company): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }

        app(SubscriptionLimitService::class)->assertCanAddEmployee($company);
    }
}
