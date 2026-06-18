<?php

namespace App\Notifications\Admin;

use App\Models\Banking\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAccountCreated extends Notification
{
    use Queueable;

    public function __construct(
        public Account $account,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->account->user;
        $typeName = $this->account->accountType?->name ?? 'Bank Account';
        return (new MailMessage)
            ->subject('[Admin] New Account Created')
            ->greeting('Hello Admin,')
            ->line("A new bank account has been opened.")
            ->line("Owner: {$user->name} ({$user->email})")
            ->line("Account Number: {$this->account->account_number}")
            ->line("IBAN: {$this->account->iban}")
            ->line("Type: {$typeName}")
            ->line("Currency: {$this->account->currency}")
            ->action('View Admin Panel', url('/admin/home'));
    }
}
