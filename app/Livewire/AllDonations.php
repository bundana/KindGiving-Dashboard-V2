<?php

namespace App\Livewire;

use App\Http\Controllers\Utilities\Helpers;
use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\Category;
use App\Models\Campaigns\Donations as CampaignDonation;
use App\Models\Campaigns\SelectedCampaign;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Livewire;

class AllDonations extends Component
{

    use WithPagination;
    #[Url()]
    public $query = '';
    protected $paginationTheme = 'bootstrap';
    protected $queryString = [
        'ref' => ['except' => ''],
        'phone' => ['except' => ''],
        'amount' => ['except' => ''],
        'donorName' => ['except' => ''],
        'method' => ['except' => ''],
        'agentID' => ['except' => ''],
        'country' => ['date' => ''],
        'email' => ['except' => ''],
        'hideDonor' => ['except' => ''],
        'keyword' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'dateFrom' => ['except' => ''],

    ];
    #[Url(keep: true)]
    public $keyword, $ref, $phone, $amount, $donorName, $email, $method, $agentID, $country, $dateFrom, $dateTo, $hideDonor = '';


    public function clearFilters()
    {
        // $this->ref = '';
        // $this->phone = '';
        // $this->amount = '';
        // $this->donorName = '';
        // $this->email = '';
        // $this->method = '';
        // $this->agentID = ''; 
        // $this->hideDonor = '';
        $this->reset();

    }
    public function placeholder()
    {
        return view('livewire.placeholders.skeleton');
    }
    public function search()
    {
        $this->resetPage();
    }

    public function filterDonations()
    {
        $user_id = 3518691972;
        $campaign = SelectedCampaign::where('user_id', $user_id)->first();
        $this->keyword = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->keyword);
        return CampaignDonation::where('campaign_id', $campaign->campaign_id)
            ->when($this->keyword, function ($query) {
                $query->where('donation_ref', 'like', "%{$this->keyword}%")
                    ->orWhere('momo_number', 'like', "%{$this->keyword}%")
                    ->orWhere('amount', 'like', "%{$this->keyword}%")
                    ->orWhere('donor_name', 'like', "%{$this->keyword}%")
                    ->orWhere('email', 'like', "%{$this->keyword}%")
                    ->orWhere('agent_id', 'like', "%{$this->keyword}%")
                    ->orWhere('country', 'like', "%{$this->keyword}%")
                    ->orWhere('hide_donor', 'like', "%{$this->keyword}%")
                    ->orWhere('method', 'like', "%{$this->keyword}%")
                    ->orWhere('status', 'like', "%{$this->keyword}%");
            })
            ->when($this->dateFrom && $this->dateTo, function ($query) {
                $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
            })
            ->latest()
            ->paginate(10);
    }

    public function render(Request $request)
    {

        return view('livewire.fundraiser.donations', ['donations' => $this->filterDonations()]);
    }

}
