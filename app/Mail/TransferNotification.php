<?php

namespace App\Mail;

use App\Models\Banking\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $direction,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Transfer ' . ($this->direction === 'sent' ? 'Sent' : 'Received'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transfer',
            with: [
                'transaction' => $this->transaction,
                'direction' => $this->direction,
            ],
        );
    }
}
