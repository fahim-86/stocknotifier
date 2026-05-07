<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceAlertReached extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Alert  $alert,
        public readonly float  $ltp,
        public readonly string $triggerType  // 'high' | 'low' | 'both'
    ) {}

    public function envelope(): Envelope
    {
        $label = match ($this->triggerType) {
            'high'  => '🔴 HIGH target reached',
            'low'   => '🟢 LOW target reached',
            'both'  => '⚡ Both targets reached',
            default => 'Alert triggered',
        };

        return new Envelope(
            subject: "[DSE Alert] {$label} — {$this->alert->trading_code}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.price_alert');
    }
}
