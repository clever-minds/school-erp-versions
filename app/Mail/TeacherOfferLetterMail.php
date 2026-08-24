<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherOfferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $offer;

    /**
     * Create a new message instance.
     */
    public function __construct($application, $offer)
    {
        $this->application = $application;
        $this->offer = $offer;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Offer Letter - ' . ($this->application->school->name ?? config('app.name')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.offer-letter',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('teacher-interviews.offer-letter-pdf', [
            'application' => $this->application,
            'offer' => $this->offer
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'Offer_Letter.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
