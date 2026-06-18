<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use App\Notifications\Admin\NewUserRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

class SendWelcomeAndAdminRegistrationEmails
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Send welcome notification to client
        $user->notify(new WelcomeNotification($user));

        // Send notification to admins
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewUserRegistered($user));
        }
    }
}
