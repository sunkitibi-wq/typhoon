<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Admin] New User Registered')
            ->greeting('Hello Admin,')
            ->line("A new user has registered on Typhoon Banking.")
            ->line("Name: {$this->user->name}")
            ->line("Email: {$this->user->email}")
            ->line("Registered At: " . now()->format('Y-m-d H:i:s'))
            ->action('View Admin Panel', url('/admin/home'));
    }
}
