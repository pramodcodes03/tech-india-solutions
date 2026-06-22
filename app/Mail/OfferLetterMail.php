<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Emails the generated Offer Letter PDF to the candidate. Sent synchronously
 * from the admin action, with the PDF bytes passed in directly — so there's no
 * queue serialization and the attachment is always populated.
 */
class OfferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public string $candidateName,
        public string $pdfData,
        public string $fileName,
    ) {}

    public function envelope(): Envelope
    {
        // Keep the SMTP-authorised from-address, but show the BUSINESS name as
        // the sender (e.g. "Noida Office") rather than the generic app name.
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                $this->business->name ?: config('mail.from.name'),
            ),
            replyTo: $this->business->email
                ? [new \Illuminate\Mail\Mailables\Address($this->business->email, $this->business->name)]
                : [],
            subject: 'Offer of Employment — '.$this->business->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.offer-letter',
            with: [
                'business' => $this->business,
                'candidateName' => $this->candidateName,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->pdfData, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
