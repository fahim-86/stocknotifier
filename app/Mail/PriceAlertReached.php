<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceAlertReached extends Mailable
{
    use Queueable, SerializesModels;

    public $tradingCode;
    public $triggerType; // 'high' or 'low'
    public $ltp;

    /**
     * Create a new message instance.
     */
    public function __construct(string $tradingCode, string $triggerType, float $ltp)
    {
        $this->tradingCode = $tradingCode;
        $this->triggerType = $triggerType;
        $this->ltp = $ltp;
    }

    public function build()
    {
        return $this->subject("DSE Alert: {$this->tradingCode} {$this->triggerType} price hit")
            ->markdown('emails.price_alert');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
