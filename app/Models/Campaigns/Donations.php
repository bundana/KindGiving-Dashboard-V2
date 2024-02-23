<?php

namespace App\Models\Campaigns;

use App\Models\Campaigns\CampaignAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donations extends Model
{
    use HasFactory;
    protected $fillable = [
        'creator',
        'campaign_id',
        'donation_ref',
        'momo_number',
        'amount',
        'donor_name',
        'platform_tip',
        'donor_public_name',
        'hide_donor',
        'comment',
        'method',
        'agent_id',
        'country'
    ];
    public function agent()
    {
        return $this->belongsTo(CampaignAgent::class, 'agent_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}