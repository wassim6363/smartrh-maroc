<?php

namespace App\Notifications;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoRequestReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DemoRequest $demoRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dr = $this->demoRequest;

        return (new MailMessage)
            ->subject('Nouvelle demande de démo SmartRH Maroc')
            ->greeting('Nouvelle demande de démo reçue')
            ->line('**Prospect:** ' . $dr->full_name)
            ->line('**Société:** ' . ($dr->company_name ?: '-'))
            ->line('**Email:** ' . ($dr->email ?: '-'))
            ->line('**Téléphone:** ' . ($dr->phone ?: '-'))
            ->line('**Pack souhaité:** ' . ($dr->target_plan ?: 'À définir'))
            ->line('**Taille entreprise:** ' . ($dr->company_size ?: '-'))
            ->line('**Message:** ' . ($dr->message ?: '-'))
            ->line('---')
            ->line('Connectez-vous à l\'administration pour traiter cette demande.');
    }
}