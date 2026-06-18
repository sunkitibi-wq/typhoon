<?php

namespace App\Notifications;

use App\Models\User;
use App\Mail\WelcomeMail;
use App\Notifications\Channels\BankDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', BankDatabaseChannel::class];
    }

    public function toMail(object $notifiable): WelcomeMail
    {
        $accountNumber = $this->user->accounts()->first()?->account_number ?? 'Pending Account Setup';
        return (new WelcomeMail($this->user, $accountNumber))
            ->to($this->user->email);
    }

    public function toBankDatabase(object $notifiable): array
    {
        $accountNumber = $this->user->accounts()->first()?->account_number ?? 'Pending Account Setup';
        return [
            'type' => 'welcome',
            'title' => 'Welcome to Typhoon!',
            'body' => "Your account has been successfully created. Account number: {$accountNumber}.",
            'user_id' => $this->user->id,
        ];
    }
}
