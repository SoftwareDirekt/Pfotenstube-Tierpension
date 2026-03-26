<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomepageReservationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing('dog.customer');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reservierungsanfrage bestätigt – Pfotenstube',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.homepage-reservation-confirmed',
        );
    }
}
