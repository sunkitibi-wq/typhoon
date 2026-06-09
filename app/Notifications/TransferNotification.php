<?php

namespace App\Notifications;

use App\Models\Banking\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->phone) {
            $channels[] = \App\Notifications\Channels\TwilioSmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Transfer Notification')
            ->line("A transfer of {$this->transaction->amount} {$this->transaction->currency} has been processed.")
            ->line("Reference: {$this->transaction->reference}")
            ->line("Status: {$this->transaction->status}")
            ->action('View Transaction', url('/banking/transactions'));
    }

    public function toSms(object $notifiable): string
    {
        return "Typhoon: Transfer of {$this->transaction->amount} {$this->transaction->currency} ({$this->transaction->status}). Ref: {$this->transaction->reference}";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'transfer',
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'reference' => $this->transaction->reference,
            'status' => $this->transaction->status,
        ];
    }
}
