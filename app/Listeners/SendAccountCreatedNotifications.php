<?php

namespace App\Listeners;

use App\Events\AccountCreated;
use App\Notifications\AccountOpenedNotification;
use App\Notifications\Admin\NewAccountCreated;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class SendAccountCreatedNotifications
{
    public function handle(AccountCreated $event): void
    {
        $account = $event->account;
        $user = $account->user;

        // Send to client
        if ($user) {
            $user->notify(new AccountOpenedNotification($account));
        }

        // Send to admins
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewAccountCreated($account));
        }
    }
}
