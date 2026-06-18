<?php

namespace App\Notifications\Admin;

use App\Models\Banking\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTransactionProcessed extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = ucfirst($this->transaction->type);
        $user = $this->transaction->user ?? $this->transaction->debitAccount?->user ?? $this->transaction->creditAccount?->user;
        $userName = $user ? $user->name : 'Unknown User';
        return (new MailMessage)
            ->subject("[Admin] Transaction Processed: {$type}")
            ->greeting('Hello Admin,')
            ->line("A new transaction of type {$type} has been processed.")
            ->line("User: {$userName}")
            ->line("Amount: {$this->transaction->amount} {$this->transaction->currency}")
            ->line("Reference: {$this->transaction->reference}")
            ->line("Description: {$this->transaction->description}")
            ->action('View Admin Panel', url('/admin/home'));
    }
}
