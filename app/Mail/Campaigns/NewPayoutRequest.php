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
class NewPayoutRequest  extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $payout, $subject, $settlementId, $balance,  $campaign;
    public $user;
    public function __construct($subject, $settlementId, $campaign_id)
    {
        $this->subject = $subject;
        $this->user =  Auth::user();
        $this->settlementId = $settlementId;
        $this->payout = PayoutSettingsInfo::where('user_id', $this->user->user_id)->first();
        $this->payout = PayoutSettlement::where('user_id', $this->user->user_id)->where('settlement_id', $this->settlementId)->first() ?: [];
        $this->campaign = Campaign::where('campaign_id', $campaign_id)->first() ?: [];
        $this->balance = FundraiserAccount::where('campaign_id', $campaign_id)->where('user_id', $this->user->user_id)->first() ?: [];
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
            view: 'layouts.mail.campaigns.new-manager-payout-request',
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
