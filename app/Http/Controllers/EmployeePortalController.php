<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeePortalController extends Controller
{
    public function login()
    {
        return view('employee.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            return redirect()->route('employee.dashboard');
        }

        return back()->withErrors(['email' => 'Identifiants invalides.']);
    }

    public function dashboard()
    {
        $employee = $this->employee();

        return view('employee.dashboard', [
            'employee' => $employee,
            'payslips' => $employee->payslips()->with('payrollPeriod')->latest()->take(5)->get(),
            'documents' => $employee->documents()->latest()->take(5)->get(),
            'generatedDocuments' => $employee->company ? $employee->hasManyGeneratedDocuments()->latest()->take(5)->get() : collect(),
            'leaveRequests' => $employee->leaveRequests()->with('leaveType')->latest()->take(5)->get(),
        ]);
    }

    public function payslips()
    {
        return view('employee.payslips', [
            'employee' => $this->employee(),
            'payslips' => $this->employee()->payslips()->with('payrollPeriod')->latest()->paginate(12),
        ]);
    }

    public function documents()
    {
        return view('employee.documents', [
            'employee' => $this->employee(),
            'documents' => $this->employee()->hasManyGeneratedDocuments()->latest()->paginate(12),
        ]);
    }

    public function leaveRequests()
    {
        return view('employee.leave-requests', [
            'employee' => $this->employee(),
            'leaveTypes' => LeaveType::query()->where('company_id', $this->employee()->company_id)->get(),
            'leaveRequests' => $this->employee()->leaveRequests()->with('leaveType')->latest()->paginate(12),
        ]);
    }

    public function storeLeaveRequest(Request $request)
    {
        $employee = $this->employee();
        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string'],
        ]);

        LeaveRequest::query()->create([
            ...$data,
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Demande de congé envoyée.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }

    private function employee()
    {
        $employee = Auth::user()?->employees()->with(['company', 'department', 'position'])->first();
        abort_unless($employee, 403);

        return $employee;
    }
}
