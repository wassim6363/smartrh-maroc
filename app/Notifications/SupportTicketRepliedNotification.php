<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly SupportTicketReply $reply,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle réponse support - SmartRH Maroc')
            ->greeting('Nouvelle réponse sur votre ticket')
            ->line('Sujet: ' . $this->ticket->subject)
            ->line('Réponse: ' . str($this->reply->message)->limit(700))
            ->line('Vous pouvez consulter le ticket depuis votre espace SmartRH Maroc.');
    }
}
