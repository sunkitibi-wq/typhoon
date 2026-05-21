<?php

namespace App\Events;

use App\Models\Banking\KycVerification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public KycVerification $verification,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->verification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'kyc.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->verification->user_id,
            'status' => $this->verification->status,
            'kyc_level' => $this->verification->kyc_level,
        ];
    }
}
