<?php

namespace App\Mail;

use App\Models\SchoolAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $audit;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(SchoolAudit $audit, $pdfPath)
    {
        $this->audit = $audit;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Audit Report: ' . ($this->audit->school ? $this->audit->school->name : 'School'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'school_audits.emails.audit_report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('Audit_Report.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
