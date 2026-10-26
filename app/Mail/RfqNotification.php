<?php

namespace App\Mail;

use App\Models\RfqRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class RfqNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RfqRequest $rfq) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "استعلام جدید: {$this->rfq->reference_number} از {$this->rfq->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rfq-notification', with: ['rfq' => $this->rfq->load('items')]);
    }
}
