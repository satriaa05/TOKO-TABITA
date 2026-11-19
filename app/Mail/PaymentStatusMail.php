<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email dikirimLANGSUNG (tidak ShouldQueue) agar tidak bergantung pada
 * queue worker / cron yang intervalnya dibatasi hosting shared.
 */
class PaymentStatusMail extends Mailable
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
            subject: 'Pembayaran '.$this->sale->invoice_number.' — '.$this->statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-status',
        );
    }
}
