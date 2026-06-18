<?php

namespace App\Notifications\Channels;

use App\Models\Banking\BankNotification;
use Illuminate\Notifications\Notification;

class BankDatabaseChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toBankDatabase')) {
            $data = $notification->toBankDatabase($notifiable);
        } elseif (method_exists($notification, 'toArray')) {
            $data = $notification->toArray($notifiable);
        } else {
            $data = [];
        }

        $title = $data['title'] ?? (method_exists($notification, 'toMail') ? $notification->toMail($notifiable)->subject : class_basename($notification));
        $body = $data['body'] ?? $data['message'] ?? '';
        $type = $data['type'] ?? 'info';

        BankNotification::create([
            'user_id' => $notifiable->id,
            'type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'status' => 'unread',
            'sent_at' => now(),
        ]);
    }
}
