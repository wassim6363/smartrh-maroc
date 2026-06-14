<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SimpleFrenchNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $subject, private readonly string $line) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->line)
            ->line('Email envoyé par SmartRH Maroc.');
    }
}
