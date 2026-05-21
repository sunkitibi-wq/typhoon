<?php

namespace App\Notifications;

use App\Models\Banking\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApproved extends Notification
{
    use Queueable;

    public function __construct(
        public KycVerification $verification,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->phone) {
            $channels[] = \App\Notifications\Channels\TwilioSmsChannel::class;
        }

        return $channels;
    }

    public function toSms(object $notifiable): string
    {
        return "Typhoon: Your KYC verification has been approved (Level: {$this->verification->kyc_level}).";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KYC Verification Approved')
            ->line('Your identity verification has been approved.')
            ->line("Level: {$this->verification->kyc_level}")
            ->action('View Dashboard', url('/banking/dashboard'))
            ->line('You now have full access to all platform features.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc_approved',
            'kyc_level' => $this->verification->kyc_level,
            'message' => 'KYC verification approved',
        ];
    }
}
