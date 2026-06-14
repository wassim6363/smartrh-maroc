<?php

namespace App\Http\Controllers\EmployeePortal;

class DashboardController extends BaseEmployeePortalController
{
    public function __invoke()
    {
        $employee = $this->employee();

        return view('employee.dashboard', [
            'employee' => $employee,
            'latestPayslip' => $employee->payslips()->with('payrollPeriod')->latest()->first(),
            'latestContract' => $employee->employeeContracts()->latest()->first(),
            'payslipsCount' => $employee->payslips()->count(),
            'contractsCount' => $employee->employeeContracts()->count(),
            'documentsCount' => $employee->generatedDocuments()->count(),
            'requestsCount' => $employee->documentRequests()->count(),
            'pendingRequestsCount' => $employee->documentRequests()->whereIn('status', ['pending', 'in_progress'])->count(),
            'openSupportTicketsCount' => $employee->supportTickets()->whereNotIn('status', ['resolved', 'closed'])->count(),
            'recentDocuments' => $employee->generatedDocuments()->latest()->take(5)->get(),
            'recentRequests' => $employee->documentRequests()->latest()->take(5)->get(),
            'recentSupportTickets' => $employee->supportTickets()->latest('updated_at')->take(5)->get(),
        ]);
    }
}
