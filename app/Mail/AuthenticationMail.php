<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Auth;

class AuthenticationMail extends Mailable
{
    use Queueable, SerializesModels;
    public $method, $subject, $otp; 
    public $user;
    /**
     * Create a new message instance.
     */

    public function __construct($subject, $user, $otp, $method)
    {
        $this->method = $method;
        $this->subject = $subject;
        $this->user =  $user;
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('support@kindgiving.org', 'KindGiving Support Team'),
            replyTo: [
                new Address('support@kindgiving.org', 'KindGiving Support Team'),
                new Address('hello@kindgiving.org', 'KindGiving Team'),
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
            view: 'partials.mails.auth.sign-up',
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
