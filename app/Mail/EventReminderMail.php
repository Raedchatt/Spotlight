<?php

namespace App\Mail;

use App\Models\Evenement;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
{
    use SerializesModels;

    public $evenement;
    public $reservation;

    /**
     * Create a new message instance.
     */
    public function __construct(Evenement $evenement, Reservation $reservation)
    {
        $this->evenement = $evenement;
        $this->reservation = $reservation;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->evenement->titre} is happening tomorrow!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.events.reminder',
            with: [
                'eventName' => $this->evenement->titre,
                'eventDate' => $this->evenement->date_debut->format('l, F j, Y \a\t g:i A'),
                'eventLocation' => $this->evenement->lieu,
                'userName' => $this->reservation->user->username ?? 'Guest',
                'ticketCount' => $this->reservation->nombre_tickets,
                'ticketType' => $this->reservation->ticket_type,
                'eventUrl' => url("/events/{$this->evenement->id}"),
            ],
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
