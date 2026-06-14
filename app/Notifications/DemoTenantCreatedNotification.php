<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoTenantCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Company $company,
        private readonly User $user,
        private readonly string $plainPassword,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre espace démo SmartRH Maroc est prêt')
            ->greeting('Bonjour ' . ($this->user->name ?: '') . ',')
            ->line('Votre espace de démonstration SmartRH Maroc a été créé avec succès.')
            ->line('**Société :** ' . $this->company->name)
            ->line('---')
            ->line('**Accès à l\'interface d\'administration :**')
            ->line('URL : ' . url('/admin'))
            ->line('Email : ' . $this->user->email)
            ->line('Mot de passe temporaire : ' . $this->plainPassword)
            ->line('---')
            ->line('Cet essai est valable 14 jours. Pensez à modifier votre mot de passe après la première connexion.')
            ->line('---')
            ->line('Les paramètres de paie, modèles de contrats et documents générés doivent être vérifiés par un expert-comptable marocain, juriste ou professionnel compétent avant utilisation officielle.');
    }
}