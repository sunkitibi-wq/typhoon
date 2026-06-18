<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class TwilioSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $to = $notifiable->phone) {
            return;
        }

        $message = $notification->toSms($notifiable);

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (empty($sid) || empty($token) || app()->environment('testing')) {
            \Illuminate\Support\Facades\Log::info("SMS Notification to {$to}: {$message}");
            return;
        }

        $twilio = new Client($sid, $token);

        $twilio->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message,
        ]);
    }
}
