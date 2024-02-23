<?php

namespace App\Mail\System\Users;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class CreateOrEdit extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    /**
     * Create a new message instance.
     */
    public function __construct(public $subject, public User $user, public $method)
    {
        $this->user = $user;
        $this->method = $method;
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('support@kindgiving.org', 'Kind Giving'),
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
            view: 'layouts.mail.system.create-edit-delete',
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
