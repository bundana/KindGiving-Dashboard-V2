<?php

namespace App\Mail\Campaigns;

use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\CampaignAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class Delete extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $subject, $status;
    /**
     * Create a new message instance.
     */
    public function __construct($subject, public Campaign $campaign, public $campaignAgents, $status)
    {
        $this->subject = $subject;
        $this->campaignAgents = $campaignAgents; // Rename the variable to make it more accurate
        $this->status = $status;
    } 
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('campaigns@kindgiving.org', 'Kind Giving Campaigns'),
            replyTo: [
                new Address('support@kindgiving.org', 'Kind Giving Support Team')
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
            view: 'layouts.mail.campaigns.delete',
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
