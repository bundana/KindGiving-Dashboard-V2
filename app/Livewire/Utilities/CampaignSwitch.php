<?php

namespace App\Livewire\Utilities;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CampaignSwitch extends Component
{
    public $user_id = '';
    public $userRole = '';
    public $campaigns = [];
    public $campaign_id = '';
    public function getCampaigns()
    {
        $this->user_id = '3518691972';
        $user = User::where('user_id', $this->user_id)->first();

        $this->userRole = $user->role;
        $this->campaigns = $user->campaigns;
    }
    public function rules()
    {
        return [
            'campaign_id' => 'required|string',
        ];
    } 
    public function switchCampaign()
    {
        $this->validate();
    }
    public function render()
    {
        $this->getCampaigns();
        return view('livewire.utilities.campaign-switch');
    }
}
