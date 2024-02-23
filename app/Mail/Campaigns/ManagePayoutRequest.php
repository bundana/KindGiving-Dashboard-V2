<?php

namespace App\Mail\Campaigns;

use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\FundraiserAccount;
use App\Models\Campaigns\PayoutSettingsInfo;
use App\Models\Campaigns\PayoutSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Auth;
// implements ShouldQueue
class ManagePayoutRequest  extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $method, $payout, $campaign, $account;
    public $user;
    public function __construct($subject, $payout, $campaign, $account, $user, $method)
    {
        $this->method = $method;
        $this->payout = $payout;
        $this->campaign = $campaign;
        $this->account = $account;
        $this->subject = $subject;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('support@kindgiving.org', 'Kind Giving Payouts'),
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
            view: 'layouts.mail.campaigns.admin-manage-payout-request',
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
