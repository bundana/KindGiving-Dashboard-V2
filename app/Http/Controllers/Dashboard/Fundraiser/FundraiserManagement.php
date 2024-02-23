<?php

namespace App\Http\Controllers\Dashboard\Fundraiser;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Exports\DonationsExport;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Campaigns\{CampaignAgent,
    CampaignTeamUsers,
    Commission,
    FundraiserAccount,
    PayoutBankList,
    PayoutSettingsInfo,
    PayoutSettlement,
    Prayer,
    SelectedCampaign
};
use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\Donations;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Utilities\Helpers;
use App\Http\Controllers\Utilities\VerifyUserName;
use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Http\Controllers\Utilities\Payment\Verify;
use App\Mail\Campaigns\AgentMail;
use App\Models\SupportTicket;
use App\Models\UserAccountNotification;
use Illuminate\Support\Facades\Auth;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class FundraiserManagement extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $userRole = $user->role;

        $campaigns = $user->campaigns;

        $donations = Donations::where('method', 'receipt')
            ->where('status', 'unpaid')
            ->where('agent_id', $user->user_id)
            ->latest()
            ->get();

        $allDonations = $donations ?: [];

        $user_id = $user->user_id;
        $selectedCampaign = $this->getCampaign();

        $selectedCampaignId = $selectedCampaign->campaign_id;
        $donations = Donations::where('campaign_id', $selectedCampaignId)->latest()->get();

        $agents = CampaignTeamUsers::where('creator', $user_id)->get();

        $fundraiserAccount = FundraiserAccount::where('campaign_id', $selectedCampaignId)->first();
        $campaignBal = 0.0;
        $accBal = 0.0;
        if (!$fundraiserAccount) {
            $campaignBal = 0.0;
            $accBal = 0.0;
        }else{
            $campaignBal = $fundraiserAccount->balance;
            $accBal = $fundraiserAccount->topup_balance;
        }
        return view('pages.fundraiser.index', compact('campaigns', 'userRole', 'user', 'allDonations', 'donations', 'agents', 'selectedCampaign', 'selectedCampaignId', 'campaignBal', 'accBal'));
    }

    public function profile()
    {
        return view('pages.fundraiser.profile');
    }

    private static function getCampaign()
    {
        $user = auth()->user();
        $selectedCampaign = [];
        $selectedCampaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$selectedCampaign) {
            $selectedCampaign = Campaign::where('manager_id', $user->user_id)->first();
        } else {
            $selectedCampaign = request()->attributes->get('selectedCampaign');
        }
        return $selectedCampaign;
    }

    public function newCampaignIndex()
    {
        return view('pages.fundraiser.campaigns.new');
    }

    public function updateCampaignIndex(Request $request)
    {
        $campaign = Campaign::where('campaign_id', $request->id)->first();
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('pages.fundraiser.campaigns.edit')->with('campaign', $campaign);
    }

    public function viewCampaign(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve donations related to the campaign
        $donations = Donations::where('campaign_id', $campaign_id)->latest()->paginate(3);

        // Retrieve prayers related to the campaign
        $prayers = Prayer::where('campaign_id', $campaign_id)->latest()->paginate(3);


        // Retrieve campaign agents associated with the campaign
        $agents = CampaignAgent::where('campaign_id', $campaign_id)->get() ?? [];
        $shortUrl = $campaign->short_url;
        if (!$shortUrl || $shortUrl == null) {
            $shortUrl = $this->generateShortUrl('https://kindgiving.org/', $campaign->slug);
            $campaign->short_url = $shortUrl;
            $campaign->save();
        }
        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('pages.fundraiser.campaigns.view')
            ->with('campaign', $campaign)
            ->with('donations', $donations)
            ->with('agents', $agents)
            ->with('shortUrl', $shortUrl)
            ->with('prayers', $prayers); // corrected variable name to 'prayers'

    }

    public function campaignVisibility(Request $request, $id)
    {
        $campaign = Campaign::where('campaign_id', $request->id)->first();

        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        if ($campaign->status != 'approved') {
            return back()->with('error', 'Campaign not approved');
        }

        $message = '';

        if ($request->has('action') && $request->action == 'hide') {
            $campaign->visibility = 'private';
            $campaign->save();

            $message = 'Private';
        } elseif ($request->has('action') && $request->action == 'show') {
            $campaign->visibility = 'public';
            $campaign->save();

            $message = 'Public';
        }
        return back()->with('success', "Campaign visibility set to $message successfully");
    }

    public function saveSelectedCampaign(Request $request)
    {
        // $user = Auth::user();
        $user_id = auth()->user()->user_id;
        $user = User::where('user_id', $user_id)->first();
        $campaign_id = $request->input('campaign_id');
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        if (!$campaign) {
            return response()->json(['message' => 'Fail to switch campaign dashboard']);

        }

        $selectedCampaign = SelectedCampaign::updateOrInsert(
            ['user_id' => $user->user_id],
            ['campaign_id' => $campaign_id, 'user_id' => $user->user_id,]
        );

        // dd($SelectedCampaign);
        return response()->json(['message' => "You are now using the {$campaign->name} campaign"]);
    }

    public function campaignPayout(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
        //get user
        // $user = $request->user();
        // if (!$user) {
        //     //send back to previous page
        //     return redirect()->back()->with('error', 'You are not allowed to access this page');
        // }
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve donations related to the campaign
        $donations = Donations::where('campaign_id', $campaign_id)->get() ?: [];

        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $settlements = PayoutSettlement::where('campaign_id', $campaign_id)->where('user_id', $campaign->manager_id)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('reference', 'like', "%$keyword%")
                    ->orWhere('settlement_id', 'like', "%$keyword%")
                    ->orWhere('amount', 'like', "%$keyword%")
                    ->orWhere('acc_name', "$keyword")
                    ->orWhere('acc_number', 'like', "%$keyword%")
                    ->orWhere('bank', "$keyword")
                    ->orWhere('status', "$keyword");
            })
            ->latest()
            ->paginate(10);


        $balance = FundraiserAccount::where('campaign_id', $campaign_id)->where('user_id', $campaign->manager_id)->first();
        // Retrieve campaign agents associated with the campaign
        $agents = CampaignAgent::where('campaign_id', $campaign_id)->get() ?? [];
        //selec payout info
        $payout = PayoutSettingsInfo::where('user_id', $campaign->manager_id)->first();
        $banks = PayoutBankList::latest()->get();
        $commission = Commission::where('user_id', $campaign->manager_id)->where('campaign_id', $campaign_id)->first();

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('pages.fundraiser.campaigns.request-payout', compact('balance', 'campaign', 'donations', 'agents', 'banks', 'payout', 'settlements', 'keyword', 'status', 'commission'));
    }


    public function supportDesk(Request $request)
    {
        $user_id = 3518691972;
        $user = User::where('user_id', $user_id)->first();
        $tickets = SupportTicket::where('user_id', $user->user_id)->latest()->paginate(10) ?: [];
        return view('pages.fundraiser.support-desk.index', compact('tickets'));
    }

    public function newSupportTicket(Request $request)
    { // Validate the request
        $validator = Validator::make($request->all(), [
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:10240', // Adjust file types and size as needed
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:800',
            'category' => 'required|string',
            'priority' => 'required|string',
        ], [
            'attachment.max' => 'The file must be at most 10 megabytes',
            'description.max' => 'The description must be at most 255 characters',
            'description.required' => 'The description field is required',
            'subject.max' => 'The subject must be at most 255 characters',
            'subject.required' => 'The subject field is required',
            'category.required' => 'The category field is required',
            'priority.required' => 'The priority field is required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        $ticket_id = Str::random(5);
        // Check if a file was provided before trying to store it
        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            // Upload attachment to CDN folder in public
            $file = $request->file('attachment');
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $filenametostore = Str::random(28) . time() . '.' . $extension;
            $destinationPath = public_path() . '/cdn/support-tickets';
            $attachmentPath = $file->move($destinationPath, $filenametostore);
            $attachmentUrl = asset('cdn/support-tickets/' . $filenametostore);
        }
        // Create the new support ticket with attachment path
        $newTicket = SupportTicket::create([
            'user_id' => $user->user_id,
            'ticket_id' => $ticket_id,
            'subject' => $request->subject,
            'message' => $request->description,
            'category' => $request->category,
            'status' => 'pending',
            'priority' => $request->priority,
            'chat' => '',
            'file_attachment' => $attachmentUrl,
        ]);


        $subject = 'New Support Ticket Created';
        // Mail::to($user->email)->send(new NewTicket($subject, $ticket_id, $attachmentUrl));
        // return ((new NewTicket($subject, $ticket_id, $attachmentUrl)))->render();
        // Check if the new ticket was created
        if ($newTicket) {
            // Redirect the user to the ticket page
            return redirect()->back()->with('success', 'Ticket created successfully');
        }
    }
}
