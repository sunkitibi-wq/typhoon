<?php

namespace App\Notifications;

use App\Models\Banking\Account;
use App\Notifications\Channels\BankDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountOpenedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Account $account,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', BankDatabaseChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeName = $this->account->accountType?->name ?? 'Bank Account';
        return (new MailMessage)
            ->subject('New Bank Account Opened')
            ->line("Your new {$typeName} has been opened successfully.")
            ->line("Account Number: {$this->account->account_number}")
            ->line("IBAN: {$this->account->iban}")
            ->line("Currency: {$this->account->currency}")
            ->action('Go to Portal', url('/client/home'));
    }

    public function toBankDatabase(object $notifiable): array
    {
        $typeName = $this->account->accountType?->name ?? 'Bank Account';
        return [
            'type' => 'account',
            'title' => 'New Account Opened',
            'body' => "Your new {$typeName} ({$this->account->account_number}) is now active.",
            'account_number' => $this->account->account_number,
        ];
    }
}
