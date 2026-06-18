<?php

namespace App\Notifications;

use App\Models\Banking\Transaction;
use App\Notifications\Channels\BankDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public string $direction = 'sent',
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', BankDatabaseChannel::class];

        if ($notifiable->phone) {
            $channels[] = \App\Notifications\Channels\TwilioSmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): \App\Mail\TransferNotification
    {
        return (new \App\Mail\TransferNotification($this->transaction, $this->direction))
            ->to($notifiable->email);
    }

    public function toSms(object $notifiable): string
    {
        return "Typhoon: Transfer of {$this->transaction->amount} {$this->transaction->currency} ({$this->transaction->status}). Ref: {$this->transaction->reference}";
    }

    public function toBankDatabase(object $notifiable): array
    {
        $prefix = $this->direction === 'sent' ? 'Sent' : 'Received';
        return [
            'type' => 'transfer',
            'title' => "Transfer {$prefix}",
            'body' => "A transfer of {$this->transaction->amount} {$this->transaction->currency} was {$this->direction}. Ref: {$this->transaction->reference}",
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'direction' => $this->direction,
        ];
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
