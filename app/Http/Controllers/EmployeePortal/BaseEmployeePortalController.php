<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Support\Facades\Auth;

abstract class BaseEmployeePortalController extends Controller
{
    protected function employee(): Employee
    {
        $employee = Auth::user()?->employees()->with(['company', 'department', 'position'])->first();
        abort_unless($employee, 403);
        abort_unless(app(SubscriptionLimitService::class)->canUseEmployeePortal($employee->company), 403);

        return $employee;
    }

    protected function abortUnlessOwn(?int $employeeId): void
    {
        abort_unless($employeeId && $employeeId === $this->employee()->id, 403);
    }
}
