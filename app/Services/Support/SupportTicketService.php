<?php

namespace App\Services\Support;

use App\Models\Employee;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketRepliedNotification;
use App\Notifications\SupportTicketResolvedNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Notification;

class SupportTicketService
{
    public function createFromEmployee(Employee $employee, array $data): SupportTicket
    {
        $ticket = SupportTicket::query()->create([
            'company_id' => $employee->company_id,
            'user_id' => $employee->user_id,
            'employee_id' => $employee->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'message' => $data['message'],
        ]);

        $this->audit('support_ticket_created', $ticket, ['employee_id' => $employee->id]);
        $this->notifyAdmin($ticket);

        return $ticket->refresh();
    }

    public function addReply(SupportTicket $ticket, string $message, ?User $user = null, ?Employee $employee = null, bool $internal = false): SupportTicketReply
    {
        $reply = $ticket->replies()->create([
            'user_id' => $user?->id,
            'employee_id' => $employee?->id,
            'message' => $message,
            'is_internal' => $internal,
        ]);

        $this->audit($internal ? 'support_ticket_internal_note_added' : 'support_ticket_replied', $ticket, [
            'reply_id' => $reply->id,
            'employee_id' => $employee?->id,
            'is_internal' => $internal,
        ]);

        if (! $internal) {
            $this->notifyReplyRecipients($ticket->refresh(), $reply, $user, $employee);
        }

        return $reply;
    }

    public function assign(SupportTicket $ticket, ?User $user): void
    {
        $old = $ticket->assigned_to_user_id;
        $ticket->forceFill([
            'assigned_to_user_id' => $user?->id,
            'assigned_to' => $user?->id,
        ])->save();

        $this->audit('support_ticket_assigned', $ticket, [
            'old_assigned_to_user_id' => $old,
            'assigned_to_user_id' => $user?->id,
        ]);
    }

    public function changeStatus(SupportTicket $ticket, string $status): void
    {
        $old = $ticket->status;
        $updates = ['status' => $status];

        if ($status === 'resolved') {
            $updates['resolved_at'] = now();
        }

        if ($status === 'closed') {
            $updates['closed_at'] = now();
        }

        $ticket->forceFill($updates)->save();

        $event = match ($status) {
            'resolved' => 'support_ticket_resolved',
            'closed' => 'support_ticket_closed',
            default => 'support_ticket_status_changed',
        };

        $this->audit($event, $ticket, ['old_status' => $old, 'new_status' => $status]);

        if ($status === 'resolved') {
            $this->notifyResolved($ticket->refresh());
        }
    }

    private function notifyAdmin(SupportTicket $ticket): void
    {
        if (! config('mail.from.address') || ! config('smartrh.support_email')) {
            return;
        }

        Notification::route('mail', config('smartrh.support_email'))
            ->notify(new SupportTicketCreatedNotification($ticket->loadMissing('company')));
    }

    private function notifyReplyRecipients(SupportTicket $ticket, SupportTicketReply $reply, ?User $user, ?Employee $employee): void
    {
        if (! config('mail.from.address')) {
            return;
        }

        if ($user && $ticket->employee?->email) {
            Notification::route('mail', $ticket->employee->email)
                ->notify(new SupportTicketRepliedNotification($ticket, $reply));
            return;
        }

        $target = $ticket->assignedUser ?: User::query()
            ->where('company_id', $ticket->company_id)
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Employee'))
            ->first();

        if ($employee && $target?->email) {
            $target->notify(new SupportTicketRepliedNotification($ticket, $reply));
        }
    }

    private function notifyResolved(SupportTicket $ticket): void
    {
        if (! config('mail.from.address') || ! $ticket->employee?->email) {
            return;
        }

        Notification::route('mail', $ticket->employee->email)
            ->notify(new SupportTicketResolvedNotification($ticket));
    }

    private function audit(string $event, SupportTicket $ticket, array $metadata = []): void
    {
        try {
            app(AuditLogger::class)->log($event, $ticket, [], [], $metadata);
        } catch (\Throwable) {
            // Audit logging must never block support workflows.
        }
    }
}
