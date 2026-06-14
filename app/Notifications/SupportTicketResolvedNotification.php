<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket support résolu - SmartRH Maroc')
            ->greeting('Votre ticket support est résolu')
            ->line('Sujet: ' . $this->ticket->subject)
            ->line('Statut: ' . $this->ticket->status_label)
            ->line('Merci d’avoir contacté le support SmartRH Maroc.');
    }
}
