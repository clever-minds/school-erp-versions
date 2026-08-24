<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherDocumentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $documentName;
    public $remarks;

    /**
     * Create a new message instance.
     */
    public function __construct($application, $documentName, $remarks)
    {
        $this->application = $application;
        $this->documentName = $documentName;
        $this->remarks = $remarks;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Rejected: Action Required - ' . env('APP_NAME', 'School'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.document-rejected',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
