<?php

namespace App\Modules\Auth\Presentation\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthPasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $resetUrl,
        private readonly int $ttlSeconds,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Recuperación de contraseña')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->line('Este enlace solo es válido por ' . $this->ttlSeconds . ' segundos.')
            ->action('Restablecer contraseña', $this->resetUrl)
            ->line('Si no solicitaste este cambio, puedes ignorar este correo.');
    }
}
