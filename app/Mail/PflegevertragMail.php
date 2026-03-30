<?php

namespace App\Mail;

use App\Models\BoardingCareAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PflegevertragMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BoardingCareAgreement $agreement)
    {
        $this->agreement->loadMissing('reservation.dog');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Pflegevereinbarung',
        );
    }

    public function content(): Content
    {
        $dogName = (string) (optional($this->agreement->reservation->dog)->name ?? 'Ihrem Tier');

        return new Content(
            view: 'emails.pflegevertrag',
            with: [
                'dogName' => $dogName,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (! $this->agreement->final_pdf_path || ! Storage::disk('local')->exists($this->agreement->final_pdf_path)) {
            return [];
        }

        $dogName = (string) (optional($this->agreement->reservation->dog)->name ?? 'Tier');

        return [
            Attachment::fromStorageDisk('local', $this->agreement->final_pdf_path)
                ->as(self::sanitizedPdfFilename($dogName))
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Download / e-mail attachment name (same rules for both).
     */
    public static function sanitizedPdfFilename(?string $dogName): string
    {
        $name = (string) ($dogName ?? 'Tier');
        $safe = Str::of($name)->ascii()->replaceMatches('/[^A-Za-z0-9\-_\s]/', '')->squish()->replace(' ', '-');

        return 'Pflegevereinbarung'.($safe !== '' ? '-'.$safe : '').'.pdf';
    }
}
