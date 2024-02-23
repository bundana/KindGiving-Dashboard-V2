<?php

namespace App\Exports;

use App\Models\Campaigns\Donations;
use App\Models\Campaigns\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class DonationsExport implements FromView
{
    protected $campaign_id;
    protected $keyword;
    protected $startDate;
    protected $endDate;
    protected $date_range;
    public $request, $method;

    public function __construct(Request $request, $campaign_id, $keyword, $startDate, $endDate, $method = null)
    {
        $this->campaign_id = $campaign_id;
        $this->keyword = $keyword;
        $this->method = $method;
        // Ensure the request is available for any additional use
        $this->request = $request;
    }

    public function view(): View
    {
        $campaign = Campaign::where('campaign_id', $this->campaign_id)->first();

        // Check if the campaign exists; if not, return an empty collection
        if (!$campaign) {
            return collect();
        }

        // Query donations based on the search keyword and date range
        $data = Donations::where('campaign_id', $this->campaign_id)
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
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('created_at', [$this->endDate, $this->endDate]);
            })
            ->latest()->get();

        return view('partials.exports.campaigns.donations', [
            'campaign' => $campaign,
            'data' => $data,
        ]);
    }
}
