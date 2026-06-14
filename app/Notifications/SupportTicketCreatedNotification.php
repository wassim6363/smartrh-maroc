<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification
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
            ->subject('Nouveau ticket support SmartRH Maroc')
            ->greeting('Nouveau ticket support')
            ->line('Sujet: ' . $this->ticket->subject)
            ->line('Société: ' . ($this->ticket->company?->name ?: '-'))
            ->line('Catégorie: ' . $this->ticket->category_label)
            ->line('Priorité: ' . $this->ticket->priority_label)
            ->line('Message: ' . str($this->ticket->message)->limit(500))
            ->line('Merci de traiter cette demande depuis l’espace admin SmartRH Maroc.');
    }
}
