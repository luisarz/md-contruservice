<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmailDTE extends Mailable
{
    use Queueable, SerializesModels;

    public $jsonPath;
    public $pdfPath;
    public Sale $sale;

    /**
     * Create a new message instance.
     */
    public function __construct($jsonPath, $pdfPath, Sale $sale)
    {
        $this->jsonPath = $jsonPath;
        $this->pdfPath = $pdfPath;
        $this->sale = $sale;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $emailFrom = env('MAIL_USERNAME');
        $nameFrom = env('APP_NAME') ?? 'Sistema DTE';

        return new Envelope(
            from: new Address($emailFrom, $nameFrom . ' - Facturación Electrónica'),
            subject: 'Factura Electrónica - ' . $this->sale->generationCode . ' - ' . env('APP_NAME'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sendDTE',
            with: ['sale' => $this->sale, 'pdfPath' => $this->pdfPath, 'jsonPath' => $this->jsonPath],
        );
    }

    /**
     * Get the attachments for the message.
     *

     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->sale->generationCode.'.pdf')
                ->withMime('application/pdf'),
            Attachment::fromPath($this->jsonPath)
                ->as($this->sale->generationCode.'.json')
                ->withMime('application/json'),

        ];
    }
}
