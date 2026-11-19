<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Sale $sale,
        public string $statusLabel,
        public string $description,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pesanan '.$this->sale->invoice_number.' — '.$this->statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shipment-status',
        );
    }
}
