<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends BaseEmployeePortalController
{
    public function index()
    {
        $employee = $this->employee();

        return view('employee.support.index', [
            'employee' => $employee,
            'tickets' => $employee->supportTickets()->latest('updated_at')->paginate(12),
        ]);
    }

    public function create()
    {
        return view('employee.support.create', [
            'employee' => $this->employee(),
            'categories' => SupportTicket::categories(),
            'priorities' => SupportTicket::priorities(),
        ]);
    }

    public function store(Request $request, SupportTicketService $service)
    {
        $employee = $this->employee();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:' . implode(',', array_keys(SupportTicket::categories()))],
            'priority' => ['required', 'in:' . implode(',', array_keys(SupportTicket::priorities()))],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $service->createFromEmployee($employee, $data);

        return redirect()->route('employee.support.show', $ticket)
            ->with('status', 'Votre ticket support a été créé.');
    }

    public function show(SupportTicket $ticket)
    {
        $employee = $this->employee();
        abort_unless($ticket->employee_id === $employee->id, 403);

        return view('employee.support.show', [
            'employee' => $employee,
            'ticket' => $ticket->load(['replies.user', 'replies.employee']),
            'publicReplies' => $ticket->replies()->where('is_internal', false)->oldest()->get(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportTicketService $service)
    {
        $employee = $this->employee();
        abort_unless($ticket->employee_id === $employee->id, 403);
        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $service->addReply($ticket, $data['message'], null, $employee);

        return redirect()->route('employee.support.show', $ticket)
            ->with('status', 'Votre réponse a été envoyée.');
    }
}
