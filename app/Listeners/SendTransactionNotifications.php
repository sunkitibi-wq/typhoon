<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Notifications\TransferNotification;
use App\Notifications\TransactionProcessedNotification;
use App\Notifications\Admin\NewTransactionProcessed;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class SendTransactionNotifications
{
    public function handle(TransactionCompleted $event): void
    {
        $transaction = $event->transaction;

        // 1. Send client notifications based on transaction type
        if ($transaction->type === 'transfer') {
            // Send Transfer Sent to the initiator/sender
            $sender = $transaction->debitAccount?->user;
            if ($sender) {
                $sender->notify(new TransferNotification($transaction, 'sent'));
            }

            // Send Transfer Received to the receiver (if local user)
            $receiver = $transaction->creditAccount?->user;
            if ($receiver && (!$sender || $sender->id !== $receiver->id)) {
                $receiver->notify(new TransferNotification($transaction, 'received'));
            }
        } else {
            // For deposits, withdrawals, refunds/reversals
            $user = $transaction->user ?? $transaction->creditAccount?->user ?? $transaction->debitAccount?->user;
            if ($user) {
                $user->notify(new TransactionProcessedNotification($transaction));
            }
        }

        // 2. Send to admins
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewTransactionProcessed($transaction));
        }
    }
}
