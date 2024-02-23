<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralProgram extends Model
{
    use HasFactory;
    protected $table = 'agent_referral_links';

    protected $fillable = [
        'agent_id',
        'referral_code',
        'link',
    ];
}
