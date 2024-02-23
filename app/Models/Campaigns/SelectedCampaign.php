<?php

namespace App\Models\Campaigns;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectedCampaign extends Model
{
 use HasFactory;
 protected $table = 'selected_dashboard_campaign';
 protected $fillable = [
  'user_id',
  'campaign_id'
 ]; 
 
}
