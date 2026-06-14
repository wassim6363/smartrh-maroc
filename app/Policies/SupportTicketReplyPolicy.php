<?php

namespace App\Policies;

use App\Models\SupportTicketReply;
use App\Models\User;

class SupportTicketReplyPolicy
{
    public function view(User $user, SupportTicketReply $reply): bool
    {
        $ticket = $reply->ticket;

        if ($reply->is_internal && $user->hasRole('Employee')) {
            return false;
        }

        return $user->isSuperAdmin()
            || (! $user->hasRole('Employee') && $user->canAccessCompany((int) $ticket->company_id))
            || ($ticket->employee_id && $user->employees()->whereKey($ticket->employee_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }
}
