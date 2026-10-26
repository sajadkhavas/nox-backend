<?php

namespace App\Mail;

use App\Models\RfqRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class RfqConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RfqRequest $rfq) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "تأیید استعلام قیمت - {$this->rfq->reference_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rfq-confirmation');
    }
}
