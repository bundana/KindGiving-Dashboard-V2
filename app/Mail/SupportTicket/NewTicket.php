<?php

namespace App\Mail\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Attachment;
// implements ShouldQueue
class NewTicket extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $subject, $ticketID, $attachment, $user, $ticket, $view;

    public function __construct($subject, $ticketID, $attachment = null)
    {
        $this->subject = $subject;
        $this->user = Auth::user();
        $this->ticketID = $ticketID;
        $this->attachment = $attachment;
        $this->ticket = SupportTicket::where('ticket_id', $ticketID)
            ->where('user_id', $this->user->user_id)
            ->first() ?: [];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('support@kindgiving.org', 'Kind Giving Support'),
            replyTo: [
                new Address('support@kindgiving.org', 'King Giving Support Team'),
            ],
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'layouts.mail.support-desk.new-ticket',
        );
    }
    public function toMailAttachment(): Attachment
    {
        return Attachment::fromPath($this->attachment);
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->attachment != null) {
            return [$this->attachment];
        } else {
            return  [];
        }
    }
}
