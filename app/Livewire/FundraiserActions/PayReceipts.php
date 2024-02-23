<?php

namespace App\Livewire\FundraiserActions;

use App\Exports\DonationsExport;
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
use Maatwebsite\Excel\Facades\Excel;

class PayReceipts extends Component
{

    use WithPagination;

    #[Url()]
    public $query = '';
    protected $paginationTheme = 'bootstrap';
    public $user, $campaign;
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
        $this->resetPage();

    }

    public function placeholder()
    {
        return view('livewire.placeholders.skeleton');
    }

    public function search()
    {
        $this->resetPage();
    }

    public function exportDonations(Request $request)
    {
        $campaign_id = $this->getCampaign()->campaign_id;
        $keyword = $this->keyword;
        // Instantiate DonationsExport with the request object
        $export = new DonationsExport($request, $campaign_id, $keyword, $this->dateTo, $this->dateFrom);
        $timeNow = date('Y-m-d H:i:s');
        // Download the Excel file
        return Excel::download($export, $timeNow . '_donations-report.xlsx');
    }

    public function filterReceipts()
    {
        $user = auth()->user();
        $campaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$campaign) {
            $campaign = Campaign::where('manager_id', $user->user_id)->first();
        }
        $campaign_id = $campaign->campaign_id;
        $this->keyword = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->keyword);
        $query = CampaignDonation::where('campaign_id', $campaign_id)->where('status', 'unpaid');

        if ($this->method != null) {
            $query->where('status', $this->method);
        }

        $query->when($this->keyword, function ($query) {
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
            });

        return $query->latest()->paginate(10);
    }

    public function render(Request $request)
    {

        return view('livewire.fundraiser.pay-receipts', ['receipts' => $this->filterReceipts()]);
    }

    private function getCampaign()
    {
        $user = auth()->user();
        $campaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$campaign) {
            $campaign = Campaign::where('manager_id', $user->user_id)->first();
        }
        return $campaign;
    }
}
