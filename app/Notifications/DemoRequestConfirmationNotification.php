<?php

namespace App\Notifications;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoRequestConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DemoRequest $demoRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre demande de démo SmartRH Maroc')
            ->greeting('Bonjour ' . ($this->demoRequest->full_name ?: '') . ',')
            ->line('Merci pour votre demande de démonstration SmartRH Maroc.')
            ->line('Notre équipe vous contactera dans les plus brefs délais pour vous présenter notre solution de gestion RH, paie et contrats adaptée au Maroc.')
            ->line('**Pack souhaité :** ' . ($this->demoRequest->target_plan ?: 'À définir'))
            ->line('**Société :** ' . ($this->demoRequest->company_name ?: '-'))
            ->line('En attendant, vous pouvez découvrir nos packs sur notre site.')
            ->line('---')
            ->line('Les paramètres de paie, modèles de contrats et documents générés doivent être vérifiés par un expert-comptable marocain, juriste ou professionnel compétent avant utilisation officielle.');
    }
}