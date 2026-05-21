<?php

namespace App\Notifications;

use App\Models\Banking\CryptoWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CryptoWithdrawalRequested extends Notification
{
    use Queueable;

    public function __construct(
        public CryptoWithdrawal $withdrawal,
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
        return "Typhoon: Crypto withdrawal of {$this->withdrawal->amount} requested. Status: {$this->withdrawal->status}.";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Crypto Withdrawal Request')
            ->line("A withdrawal of {$this->withdrawal->amount} has been requested.")
            ->line("Status: {$this->withdrawal->status}")
            ->action('Review Withdrawal', url('/admin/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'crypto_withdrawal',
            'amount' => $this->withdrawal->amount,
            'currency' => $this->withdrawal->cryptoCurrency?->code,
            'status' => $this->withdrawal->status,
        ];
    }
}
