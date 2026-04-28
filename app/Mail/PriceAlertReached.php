<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use App\Models\Alert;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceAlertReached extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Alert  $alert,
        public float  $ltp,
        public string $triggerType   // 'high' or 'low'
    ) {}

    public function envelope(): Envelope
    {
        $direction = $this->triggerType === 'high' ? '🔴 HIGH' : '🟢 LOW';
        return new Envelope(
            subject: "[DSE Alert] {$direction} triggered for {$this->alert->trading_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.price_alert',
        );
    }
}
