<?php

namespace App\Notifications;

use App\Models\Banking\Transaction;
use App\Notifications\Channels\BankDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', BankDatabaseChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = ucfirst($this->transaction->type);
        return (new MailMessage)
            ->subject("Transaction Completed: {$type}")
            ->line("A transaction of type {$type} has been successfully completed on your account.")
            ->line("Amount: {$this->transaction->amount} {$this->transaction->currency}")
            ->line("Reference: {$this->transaction->reference}")
            ->line("Description: {$this->transaction->description}")
            ->action('View Transactions', url('/banking/transactions'));
    }

    public function toBankDatabase(object $notifiable): array
    {
        $type = ucfirst($this->transaction->type);
        return [
            'type' => 'transaction',
            'title' => "Transaction Completed: {$type}",
            'body' => "Your {$this->transaction->type} of {$this->transaction->amount} {$this->transaction->currency} is completed. Ref: {$this->transaction->reference}",
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
        ];
    }
}
