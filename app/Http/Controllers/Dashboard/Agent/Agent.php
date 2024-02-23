<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utilities\Helpers;
use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Http\Controllers\Utilities\Payment\Verify;
use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\CampaignAgent;
use App\Models\Campaigns\Commission;
use App\Models\Campaigns\Donations;
use App\Models\Campaigns\FundraiserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Agent extends Controller
{
    public function index()
    {
        return view('agent.index');
    }

    public function campaigns(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        // Retrieve campaigns associated with the agent
        $campaignAgent = CampaignAgent::where('agent_id', auth()->user()->user_id)
            ->with('campaign') // Eager load the "campaign" relationship
            ->get();

        // Extract campaign IDs associated with the agent
        $campaignIds = $campaignAgent->pluck('campaign_id')->toArray();

        // Retrieve campaigns based on the search criteria
        $campaigns = Campaign::whereIn('campaign_id', $campaignIds)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', 'like', "%$keyword%") // Use 'like' for 'target'
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);

        foreach ($campaigns as $campaign) {
            $shortUrl = $campaign->short_url;
            if (!$shortUrl || $shortUrl == null) {
                $shortUrl = $this->generateShortUrl('https://kindgiving.org/', $campaign->slug);
                $campaign->short_url = $shortUrl;
                $campaign->save();
            }
        }
        return view('agent.campaigns', compact('campaigns', 'keyword', 'status'));
    }

    public function viewCampaign(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve donations related to the campaign
        $donations = Donations::with('campaign')->where('creator', $campaign->manager_id)->where('agent_id', auth()->user()->user_id)
            ->where('status', 'paid')
            ->get() ?: [];

        // Retrieve campaign agents associated with the campaign
        $agents = CampaignAgent::where('campaign_id', $campaign_id)->get() ?? [];
        $shortUrl = $campaign->short_url;
        if (!$shortUrl || $shortUrl == null) {
            $shortUrl = $this->generateShortUrl('https://kindgiving.org/', $campaign->slug);
            $campaign->short_url = $shortUrl;
            $campaign->save();
        }
        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('agent.view-campaign')
            ->with('campaign', $campaign)
            ->with('donations', $donations)
            ->with('shortUrl', $shortUrl)
            ->with('agents', $agents);
    }

    public function donationsReceipt(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator
        $action = $request->action;

        // Retrieve campaigns associated with the agent
        $campaignAgent = CampaignAgent::where('agent_id', auth()->user()->user_id)
            ->with('campaign') // Eager load the "campaign" relationship
            ->get();

        // Extract campaign IDs associated with the agent
        $campaignIds = $campaignAgent->pluck('campaign_id')->toArray();

        // Retrieve campaigns based on the search criteria
        $campaigns = Campaign::whereIn('campaign_id', $campaignIds)->where('status', "approved")
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', 'like', "%$keyword%") // Use 'like' for 'target'
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('agent.all-campaigns-receipts')->with('campaigns', $campaigns)->with('status', $status)->with('keyword', $keyword)->with('action', $action);
    }

    public function campaignReceiptIndex(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        $donations = Donations::with('campaign')->where('campaign_id', $campaign->campaign_id)->where('agent_id', auth()->user()->user_id)->when($request->filled('keyword'), function ($query) use ($request) {
            // Add existing search functionality
            $query
                ->where('donation_ref', 'like', "%{$request->keyword}%")
                ->orWhere('momo_number', $request->keyword)
                ->orWhere('amount', 'like', "%{$request->keyword}%")
                ->orWhere('donor_name', 'like', "%{$request->keyword}%")
                ->orWhere('method', 'like', "%{$request->keyword}%")
                ->orWhere('status', 'like', "%{$request->keyword}%");
        })
            ->when($request->filled('date_range'), function ($query) use ($request) {
                // Add new date range filter
                [$startDate, $endDate] = explode(' - ', $request->input('date_range'));
                $query->whereBetween('created_at', [date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate))]);
            })
            ->latest()
            ->paginate(50);


        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('agent.my-campaign-receipts', compact('donations', 'campaign'));
    }

    public function newDonationsReceiptIndex(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('agent.create-receipt')->with('campaign', $campaign);
    }

    public function payReceiptIndex(Request $request)
    {

        if (!session()->has('selected_donations')) {
            return redirect(route('agent.donation-receipts'))->with('error', 'No donations receipts selected');
        }
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('agent.pay-receipt')->with('campaign', $campaign);
    }

    public function payReceipt(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donations = $request->input('donations', []);

        $donationRefs = array_column($donations, 'donation_ref');

        $rules = [
            'trnx_ref' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'donations' => ['required']
        ];

        // Validation custom error messages
        $messages = [
            'trnx_ref.required' => 'The Transaction reference is required.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a numeric value.',
            'donations.required' => 'The donations list field is required.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }


        $agentName = auth()->user()->name;
        $amount = $request->input('amount');
        $agentNumber = auth()->user()->phone_number;
        $transRef = $request->input('trnx_ref');
        $verifyTransaction = new Verify($transRef);
        $response = $verifyTransaction->verifyTransaction(); // Corrected function name to match the updated Verify class
        $response = json_decode($response, true);

        if (isset($response['status']) && $response['status'] == false) {
            return response()->json(['success' => false, 'message' => $response['error'] . ", contact support with ref' . $transRef"]);
        }


        $commission = Commission::where('user_id', $campaign->manager_id)->where('campaign_id', $campaign_id)->first();
        $balance = FundraiserAccount::where('campaign_id', $request->id)->where('user_id', $campaign->manager_id)->first();

        //platform usage comission is 15%
        $plaftformCommisionPercentage = '';

        if (isset($commission)) {
            $plaftformCommisionPercentage = $commission->commission;
        } else {
            $plaftformCommisionPercentage = 15;
        }

        $plaftformCommision = max(0, number_format(($amount * $plaftformCommisionPercentage) / 100, 2));
        $remainingAmount = max(0, ($amount * $plaftformCommisionPercentage) / 100);
        // $remainingAmount = max(0, number_format($amount - $plaftformCommision, 2));
        $remainingAmount = max(0, number_format($amount - $plaftformCommision, 2));
        $remainingAmount = str_replace(',', '', $remainingAmount);


        //insert or update balance
        if ($balance) {
            $accBalance = $balance->balance ?: 0;
            $balance->update([
                'balance' => DB::raw($accBalance + $remainingAmount),
            ]);
        } else {

            FundraiserAccount::create([
                'campaign_id' => $request->id,
                'user_id' => $campaign->manager_id,
                'balance' => $remainingAmount,
            ]);
        }


        if (isset($response['status']) && $response['status'] == true) {
            foreach ($donationRefs as $donationRef) {
                //find the donation before updating 
                Donations::where(
                    [
                        'donation_ref' => $donationRef,
                        'creator' => $campaign->manager_id,
                    ]
                )->update([
                            'status' => 'paid'
                        ]);
            }
            $request->session()->forget('selected_donations');
            //send sms notices
            $sms_content = "Hi $agentName,";
            $sms_content .= "you have successfully paid all of your cash for selected donations\n";
            $sms_content .= "God bless you";
            $sms = new SMS($agentNumber, $sms_content);
            $sms->singleSendSMS();
            return response()->json(['success' => true, 'message' => 'Payment verified successfully']);
        } else {
            return response()->json(['success' => true, 'message' => 'Payment verification failed, contact support with ref "' . $transRef . '"']);
        }
    }

    public function donationsStats(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator


        // Retrieve campaigns associated with the agent
        $campaignAgent = CampaignAgent::where('agent_id', auth()->user()->user_id)
            ->with('campaign') // Eager load the "campaign" relationship
            ->get();

        // Extract campaign IDs associated with the agent
        $campaignIds = $campaignAgent->pluck('campaign_id')->toArray();

        // Retrieve campaigns based on the search criteria
        $campaigns = Campaign::whereIn('campaign_id', $campaignIds)->where('status', "approved")
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', 'like', "%$keyword%") // Use 'like' for 'target'
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('agent.campaign-donations-reports')->with('campaigns', $campaigns)->with('status', $status)->with('keyword', $keyword);
    }

    public function viewDonationsStats(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: '';
        $method = $request->input('method') ?: '';
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        // Initialize startDate and endDate variables
        $startDate = null;
        $endDate = null;
        $date_range = $request->input('date_range') ?? "";
        $dateRange = $request->input('date_range');

        // Query donations based on the search keyword and date range 
        $donations = Donations::with('campaign')->where('creator', $campaign->manager_id)
            ->where('agent_id', auth()->user()->user_id)
            ->where('status', 'paid')
            ->when($method, function ($query) use ($method) {
                $query->where('method', $method);
            })
            ->when($keyword, function ($query) use ($keyword) {
                // Add existing search functionality
                $query->where(function ($query) use ($keyword) {
                    $query->where('donation_ref', 'like', "%$keyword%")
                        ->orWhere('momo_number', "$keyword")
                        ->orWhere('method', "$keyword")
                        ->orWhere('amount', 'like', "%$keyword%")
                        ->orWhere('donor_name', 'like', "%$keyword%")
                        ->orWhere('method', 'like', "%$keyword%")
                        ->orWhere('status', 'like', "%$keyword%");
                });
            })
            ->when($request->filled('date_range'), function ($query) use ($dateRange) {
                $dateRange = explode(' - ', $dateRange);
                $startDate = date('Y-m-d', strtotime($dateRange[0]));
                $endDate = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->latest()
            ->paginate(50);


        return view(
            'agent.single-campaign-donations-reports',
            compact('donations', 'keyword', 'campaign', 'date_range', 'method')
        );
    }


    public function paymentLinksIndex(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        // Retrieve campaigns associated with the agent
        $campaignAgent = CampaignAgent::where('agent_id', auth()->user()->user_id)
            ->with('campaign') // Eager load the "campaign" relationship
            ->get();

        // Extract campaign IDs associated with the agent
        $campaignIds = $campaignAgent->pluck('campaign_id')->toArray();

        // Retrieve campaigns based on the search criteria
        $campaigns = Campaign::whereIn('campaign_id', $campaignIds)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', 'like', "%$keyword%") // Use 'like' for 'target'
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('agent.payment-links-index', compact('campaigns', 'keyword', 'status'))->with('status', $status)->with('keyword', $keyword);
    }

    public function paymentLinksForm(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('agent.payment-links-form')->with('campaign', $campaign);
    }

    public function paymentLinkDirectIndex(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        // Retrieve campaigns associated with the agent
        $campaignAgent = CampaignAgent::where('agent_id', auth()->user()->user_id)
            ->with('campaign') // Eager load the "campaign" relationship
            ->get();

        // Extract campaign IDs associated with the agent
        $campaignIds = $campaignAgent->pluck('campaign_id')->toArray();

        // Retrieve campaigns based on the search criteria
        $campaigns = Campaign::whereIn('campaign_id', $campaignIds)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', 'like', "%$keyword%") // Use 'like' for 'target'
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('agent.web-payment-index', compact('campaigns', 'keyword', 'status'))->with('status', $status)->with('keyword', $keyword);
    }

    public function paymentLinkDirectForm(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('agent.web-payment-form')->with('campaign', $campaign);
    }
}
