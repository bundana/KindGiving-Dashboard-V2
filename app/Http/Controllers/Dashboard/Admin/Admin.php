<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Campaigns\CampaignsPayout;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Exports\DonationsExport;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Campaigns\{CampaignAgent, CampaignTeamUsers, Commission, FundraiserAccount};
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
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Admin extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function profile()
    {
        return view('admin.profile');
    }
    public function campaignIndex(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('manager_id', 'like', "%$keyword%")
                    ->orWhere('target', "$keyword")
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
        return view('admin.campaigns.index', compact('campaigns', 'keyword', 'status'));
    }

    public function newCampaignIndex()
    {
        return view('admin.campaigns.new');
    }
    public function updateCampaignIndex(Request $request)
    {
        $campaign = Campaign::where('campaign_id', $request->id)->first();
        if (!$campaign) {
            return redirect(route('admin.campaigns'))->with('error', 'Campaign not found');
        }
        return view('admin.campaigns.edit')->with('campaign', $campaign);
    }

    public function viewCampaign(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return redirect(route('admin.campaigns'))->with('error', 'Campaign not found');
        }
        // Retrieve donations related to the campaign
        $donations = Donations::where('campaign_id', $campaign_id)->get() ?: [];
        // Retrieve campaign agents associated with the campaign
        $agents = CampaignAgent::where('campaign_id', $campaign_id)->get() ?? [];


        $shortUrl = $campaign->short_url;
        if (!$shortUrl || $shortUrl == null) {
            $shortUrl = $this->generateShortUrl('https://kindgiving.org/', $campaign->slug);
            $campaign->short_url = $shortUrl;
            $campaign->save();
        }

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('admin.campaigns.view')
            ->with('campaign', $campaign)
            ->with('donations', $donations)
            ->with('shortUrl', $shortUrl)
            ->with('agents', $agents);
    }


    public function allAgents(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', "$keyword")
                    ->orWhere('manager_id', "$keyword")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('admin.campaigns.all-agents', compact('campaigns', 'keyword', 'status'));
    }
    public function campaignAgents(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
            // 'status' => 'nullable|in:active,inactive',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: '';

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve campaign agents associated with the campaign
        $agents = CampaignAgent::with('user')
            ->where('campaign_id', $campaign_id)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->whereHas('user', function ($userQuery) use ($keyword) {
                    $userQuery->where('name', 'like', "%$keyword%")
                        ->orWhere('email', 'like', "%$keyword%")
                        ->orWhere('phone_number', "$keyword");
                });
            })
            ->latest()
            ->paginate(50);
        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('admin.campaigns.campaign-agents')
            ->with('campaign', $campaign)
            ->with('agents', $agents)->with('status', $status)->with('keyword', $keyword);
    }

    public function viewAgent(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $request->campaignId;
        $agent_id = $request->agentId;
        $keyword = $request->input('keyword');

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve campaign agents associated with the campaign
        $agent = CampaignAgent::with('user', 'donations')
            ->where('campaign_id', $campaign_id)
            ->where('agent_id', $agent_id)
            ->first();

        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }
        //  dd($request->all());

        // Retrieve agent donations with search functionality
        $donations = $agent->donations()
            ->when($keyword, function ($query) use ($keyword) {
                // Add existing search functionality
                $query->where('donation_ref', 'like', "%$keyword%")
                    ->orWhere('momo_number', "$keyword")
                    ->orWhere('amount', 'like', "%$keyword%")
                    ->orWhere('donor_name', 'like', "%$keyword%")
                    ->orWhere('method', 'like', "%$keyword%")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            // Add new date range filter
            ->when($request->filled('date_range'), function ($query) use ($request) {
                $dateRange = explode(' - ', $request->input('date_range'));
                $startDate = date('Y-m-d', strtotime($dateRange[0]));
                $endDate = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->latest()
            ->paginate(50);

        // Render the 'view' template and pass the campaign, agent, donations, and keyword data
        return view('admin.campaigns.view-agent')
            ->with('campaign', $campaign)
            ->with('agent', $agent)
            ->with('donations', $donations)
            ->with('keyword', $keyword);
    }


    public function editAgent(Request $request)
    {
        // Retrieve the campaign ID and agent ID from the request
        $campaign_id = $request->campaignId;
        $agent_id = $request->agentId;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve campaign agents associated with the campaign
        $agent = CampaignAgent::with('user', 'donations')
            ->where('campaign_id', $campaign_id)
            ->where('agent_id', $agent_id)
            ->first();

        // Check if the agent exists; if not, redirect back with an error message
        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:5'],
            'email_address' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($agent->user->user_id, 'user_id'),
            ],
            'phone_number' => [
                'required',
                'numeric',
                'digits:10',
                Rule::unique('users', 'phone_number')->ignore($agent->user->user_id, 'user_id'),
            ],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                File::image()->min('1kb')->max('10000kb'), // Increased max size to 10MB
            ],
            'password' => ['nullable', 'confirmed'],
        ], [
            'name.required' => 'The agent name is required.',
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a valid number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'image.file' => 'The uploaded file must be a valid file.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must be at most 10 megabytes.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update agent information
        $agent->user->name = $request->input('name');
        $agent->user->email = $request->input('email_address');
        $agent->user->phone_number = $request->input('phone_number');

        // Update password only if provided
        if ($request->filled('password')) {
            $agent->user->password = bcrypt($request->input('password'));
        }

        // Get File Extension
        $imagefullPathUrl = "";
        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();
            $subfolder = 'cdn/avatar/agents/'; // Generate Filename with Subfolder
            $filenametostore = $subfolder . Str::uuid() . time() . '.' . $extension;

            // Upload File to External Server (FTP)
            Storage::disk('ftp')->put($filenametostore, file_get_contents($request->file('image')->getRealPath()));
            // Helpers::uploadImageToFTP($filenametostore, $request->file('image'));

            // Get Full Path URL
            $basePath = "https://asset.kindgiving.org/"; // Replace with your actual base URL
            $imagefullPathUrl = $basePath . $filenametostore;
        } else {
            $imagefullPathUrl = $agent->user->avatar; // Fix the key here ('avatar' instead of 'avatart')
        }
        $agent->user->update([
            'avatar' => $imagefullPathUrl, // Fix the key here ('avatar' instead of 'avatart')
        ]);
        $agent->user->save();
        // Redirect to a success route
        return redirect()->back()->with('success', 'Agent updated successfully');
    }

    public function deleteAgent(Request $request)
    {
        // Retrieve the campaign ID and agent ID from the request
        $campaign_id = $request->campaignId;
        $agent_id = $request->agentId;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Retrieve campaign agents associated with the campaign
        $agent = CampaignAgent::with('user', 'donations')
            ->where('campaign_id', $campaign_id)
            ->where('agent_id', $agent_id)
            ->first();

        // Check if the agent exists; if not, redirect back with an error message
        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }

        // Delete agent
        $agent->delete();

        // Redirect to a success route
        return redirect(route('admin.campaign-agents', [$campaign_id]))->with('success', 'Agent deleted successfully');
    }

    public function allDonors(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', "$keyword")
                    ->orWhere('manager_id', "$keyword")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('admin.campaigns.all-donors', compact('campaigns', 'keyword', 'status'))->with('status', $status)->with('keyword', $keyword);
    }

    public function campaignDonors(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: '';

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donations = Donations::where('campaign_id', $campaign_id)->when($keyword, function ($query) use ($keyword) {
            // Add existing search functionality
            $query->where('donation_ref', 'like', "%$keyword%")
                ->orWhere('momo_number', "$keyword")
                ->orWhere('amount', 'like', "%$keyword%")
                ->orWhere('donor_name', 'like', "%$keyword%")
                ->orWhere('method', 'like', "%$keyword%")
                ->orWhere('status', 'like', "%$keyword%");
        })
            // Add new date range filter
            ->when($request->filled('date_range'), function ($query) use ($request) {
                $dateRange = explode(' - ', $request->input('date_range'));
                $startDate = date('Y-m-d', strtotime($dateRange[0]));
                $endDate = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->latest()
            ->paginate(50);

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('admin.campaigns.campaign-donors')
            ->with('campaign', $campaign)
            ->with('donations', $donations)->with('status', $status)->with('keyword', $keyword);
    }

    public function donationsReceipt(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $campaigns = Campaign::where('manager_id', auth()->user()->user_id)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', "$keyword")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('admin.campaigns.all-receipts')->with('campaigns', $campaigns)->with('status', $status)->with('keyword', $keyword);
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
        return view('admin.campaigns.new-receipt')->with('campaign', $campaign);
    }

    public function createReceipt(Request $request)
    {
        // Validation custom error messages
        // Validate the request
        $validator = Validator::make($request->all(), [
            'momo_number' => ['required', 'numeric', 'digits:10'],
            'amount' => ['required', 'numeric'],
            'donor_name' => ['required']
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a valid amount.',
            'momo_number.required' => 'The phone number field is required.',
            'momo_number.digits' => 'The momo number must be exactly 10 digits.',
            'donor_name.required' => 'The Donor name field is required.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $campaign_id = $request->id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donarName = $request->input('donor_name');
        $amount = $request->input('amount');
        $momoNumber = $request->input('momo_number');

        $transRef = str::random(18);
        $affected = Donations::create(
            [
                'creator' => $campaign->manager_id,
                'campaign_id' => $campaign_id,
                'donation_ref' => $transRef,
                'momo_number' => $momoNumber,
                'amount' => $amount,
                'donor_name' => $donarName,
                'method' => 'receipt',
                'agent_id' => auth()->user()->user_id,
                'status' => 'unpaid'
            ]
        );

        //send sms notices
        $shortName = Helpers::getFirstName($donarName);
        $sms_content = "Dear $shortName, thank you for your GHS $amount donation. ";
        $sms_content .= "Your reference: $transRef, Agent ID: " . auth()->user()->user_id . ". ";
        $sms_content .= "God bless you.";
        $sms = new SMS($request->input('momo_number'), $sms_content);
        $sms->singleSendSMS();

        $shortName = Helpers::getFirstName(auth()->user()->name);
        $sms_content = "Hi $shortName, donation receipt created for $momoNumber ($shortName) of GHS $amount. ";
        $sms_content .= "Receipt reference: $transRef.";
        $sms = new SMS(auth()->user()->phone_number, $sms_content);
        //    $sms->singleSendSMS();

        return back()->with('success', 'Donation receipt created successfully, Receipt ID ' . $transRef)->with('referenced', $transRef);
    }

    public function verifyDonation(Request $request)
    {
        // Validation rules
        $rules = [
            'donation_ref' => ['required', 'string'],
            'momo_number' => ['required', 'numeric'],
            'amount' => ['required', 'numeric'],
            'donor_name' => ['required']
        ];

        // Validation custom error messages
        $messages = [
            'donation_ref.required' => 'The Transaction reference is required.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a numeric value.',
            'donor_name.required' => 'The Donor name field is required.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }
        $donarName = $request->input('donor_name');
        $amount = $request->input('amount');
        $momoNumber = $request->input('momo_number');

        $transRef = $request->input('donation_ref') ?: ' ';
        $verifyTransaction = new Verify($transRef);
        $response = $verifyTransaction->verifyTransaction(); // Corrected function name to match the updated Verify class
        $response = json_decode($response, true);


        if (isset($response['status']) && $response['status'] == true) {
            Donations::create(
                [
                    'donation_ref' => $transRef,
                    'momo_number' => $momoNumber,
                    'amount' => $amount,
                    'donor_name' => $donarName,
                    'method' => 'web',
                    'agent_id' => auth()->user()->user_id,
                ]
            );

            //send sms notices
            $shortName = Helpers::getFirstName($donarName);
            $sms_content = "Dear $shortName, thank you for your GHS $amount donation. ";
            $sms_content .= "Your reference: $transRef, Agent ID: " . auth()->user()->user_id . ". ";
            $sms_content .= "God bless you.";
            $sms = new SMS($request->input('momo_number'), $sms_content);
            $sms->singleSendSMS();

            return response()->json(['success' => true, 'message' => 'Payment verified successfully']);
        } else {
            // return response()->json(['success' => false, 'message' => $response['error']]);
            return response()->json(['success' => true, 'message' => 'Payment verification failed, contact support with ref' . $transRef]);
        }
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

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('admin.campaigns.campaign-receipts')
            ->with('campaign', $campaign)->with('request', $request);
    }

    public function payReceiptIndex(Request $request)
    {
        if (!session()->has('selected_donations')) {
            return redirect(route('admin.donation-receipts'))->with('error', 'No donations receipts selected');
        }
        // Retrieve the campaign ID from the request
        $campaign_id = $request->id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('admin.campaigns.pay-donation-receipts')->with('campaign', $campaign);
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


        $commission = Commission::where('user_id', auth()->user()->user_id)->where('campaign_id', $campaign_id)->first();
        $balance = FundraiserAccount::where('campaign_id', $request->id)->where('user_id', auth()->user()->user_id)->first();

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
                'user_id' => auth()->user()->user_id,
                'balance' => $remainingAmount,
            ]);
        }




        if (isset($response['status']) && $response['status'] == true) {
            foreach ($donationRefs as $donationRef) {
                //find the donation before updating 
                Donations::where(
                    [
                        'donation_ref' => $donationRef,
                        'creator' => auth()->user()->user_id,
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
            return response()->json(['success' => true, 'message' => 'Payment verification failed, contact support with ref' . $transRef]);
        }
    }
    public function storeorRemoveSelectedReceipts(Request $request)
    {
        if ($request->has('remove_donation_ref')) {
            $donations = session()->has('selected_donations') ? session()->get('selected_donations') : [];
            $removeDonationRef = $request->input('remove_donation_ref');
            // Remove the specific donation reference from the array
            $donations = array_diff($donations, [$removeDonationRef]);

            session(['selected_donations' => $donations]);

            return back()->with('success', "Donation reference $removeDonationRef removed successfully");
        } else {
            $donations = $request->input('donations', []);

            session(['selected_donations' => $donations]);

            return response()->json(['success' => true]);
        }
    }

    public function donationsStats(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%")
                    ->orWhere('target', "$keyword")
                    ->orWhere('manager_id', "$keyword")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(50);
        return view('admin.campaigns.donations-reports-index')->with('campaigns', $campaigns)->with('status', $status)->with('keyword', $keyword);
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
        $donations = Donations::when($method, function ($query) use ($method) {
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
            'admin.campaigns.all-donations-reports',
            compact('donations', 'keyword', 'campaign', 'date_range', 'method', 'method')
        );
    }

    public function exportDonations(Request $request)
    {
        // dd($request->all());
        $campaign_id = $request->campaign_id;
        $keyword = $request->keyword;
        $date_range = $request->date_range;
        $method = $request->method;

        $date_range2 = str_replace('/', '_', $date_range);
        $hey1 = "$campaign_id $keyword $date_range2 $method";

        // Instantiate DonationsExport with the request object
        $export = new DonationsExport($request, $campaign_id, $keyword, $date_range, $method);
        $fileName = Str::snake($hey1) . "_" . time();

        // Download the Excel file 
        return Excel::download($export, "$fileName-donations-report.xlsx", \Maatwebsite\Excel\Excel::XLSX);
    }

    public function users(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive', // Uncommented the status validation
        ]);

        // Retrieve validated inputs
        $keyword = $request->input('keyword');
        $status = $request->input('status');

        // Retrieve campaign agents associated with the
        $agents = CampaignTeamUsers::with('user', 'campaign', 'creator')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->whereHas('user', function ($userQuery) use ($keyword) {
                    $userQuery->where('name', 'like', "%$keyword%")
                        ->orWhere('email', 'like', "%$keyword%")
                        ->orWhere('phone_number', 'like', "%$keyword%");
                });
            })
            ->latest()
            ->paginate(50);
        // dd($agents);
        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('admin.campaigns.users.index', compact('agents', 'status', 'keyword'));
    }


    public function viewUser(Request $request)
    {
        // Retrieve the campaign ID from the request 
        $agent_id = $request->id;
        $keyword = $request->input('keyword');

        $allCampaigns = Campaign::latest()
            ->paginate(10) ?: [];

        $agentCampaigns = CampaignAgent::with('user', 'campaign')->where('agent_id', $agent_id)
            ->latest()
            ->paginate(50);

        // Retrieve campaign agents associated with the campaign
        $agent = CampaignTeamUsers::with('user', 'donations')
            ->where('user_id', $agent_id)
            ->first();

        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }

        // Retrieve agent donations with search functionality
        $donations = $agent->donations()->with('campaign')
            ->when($keyword, function ($query) use ($keyword) {
                // Add existing search functionality
                $query->where('donation_ref', 'like', "%$keyword%")
                    ->orWhere('momo_number', "$keyword")
                    ->orWhere('amount', 'like', "%$keyword%")
                    ->orWhere('donor_name', 'like', "%$keyword%")
                    ->orWhere('method', 'like', "%$keyword%")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            // Add new date range filter
            ->when($request->filled('date_range'), function ($query) use ($request) {
                $dateRange = explode(' - ', $request->input('date_range'));
                $startDate = date('Y-m-d', strtotime($dateRange[0]));
                $endDate = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->latest()
            ->paginate(50);

        // Render the 'view' template and pass the campaign, agent, donations, and keyword data
        return view('admin.campaigns.users.view')
            ->with('agent', $agent)
            ->with('donations', $donations)
            ->with('keyword', $keyword)
            ->with('allCampaigns', $allCampaigns)
            ->with('agentCampaigns', $agentCampaigns);
    }


    public function assignUserToCampaign(Request $request)
    {

        $campaign_id = $request->campaignId;
        $agent_id = $request->id;

        $campaign = Campaign::where('campaign_id', $campaign_id)->first();
        $agent = CampaignTeamUsers::with('user')->where('user_id', $agent_id)->first();

        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        // if ($campaign->status  != 'approved') {
        //     return back()->with('error', 'Campaign not approved');
        // }

        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }
        if ($request->has('assign')) {
            /*
        add agents to the campaign agents list,
        check if agent is already in the list
        */
            $campaignAgent = CampaignAgent::where('campaign_id', $campaign->campaign_id)->where('agent_id', $agent->user->user_id)->first();
            if ($campaignAgent) {
                return back()->with('error', 'Agent already assigned to campaign');
            } else {
                CampaignAgent::create([
                    'campaign_id' => $campaign->campaign_id,
                    'agent_id' => $agent->user->user_id,
                    'name' => $agent->user->name,
                    'status' => 'active',
                    'creator' => auth()->user()->user_id
                ]);
            }

            $campaignName = Str::of($campaign->name)->limit(15);
            $shortName = Helpers::getFirstName($agent->user->name);
            $sms_content = "Hi $shortName,you have been assigned as agent for  $campaignName";
            $sms_content .= "Check you mail for more details.";
            $sms = new SMS($agent->user->phone_number, $sms_content);
            $sms->singleSendSMS();

            $subject = "Campaign Assignment";
            $agent = $campaignAgent;
            Mail::to($agent->user->email)->send(new AgentMail($subject, $campaign, $agent, 'assign'));

        } elseif ($request->has('unassign')) {
            $campaignAgent = CampaignAgent::where('campaign_id', $campaign->campaign_id)->where('agent_id', $agent->user->user_id)->first();
            if ($campaignAgent) {
                $subject = "You have been unassigned from the campaign";
                Mail::to($agent->user->email)->send(new AgentMail($subject, $campaign, $agent, 'unassign'));

                $campaignAgent->delete();

                return back()->with('success', 'Agent was unassigned from the campaign');
            } else {
                return back()->with('error', 'Agent not assigned to campaign');
            }
        }


        return back()->with('success', 'Agent assigned to campaign successfully');
    }

    public function addAgentUser(Request $request)
    {

        // Retrieve the campaign ID from the request
        $campaign_id = $request->campaign_id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();
        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }


        // Validation rules
        $validator = Validator::make($request->all(), [
            'email_address' => ['required', 'email', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'digits:10', Rule::unique('users', 'phone_number')],
            'password' => ['nullable', 'confirmed'],
            'name' => ['required', 'string', 'min:5'],
            'campaign_id' => ['required', 'string'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                File::image()->min('1kb')->max('10000kb'), // Increased max size to 10MB
            ],
        ]);

        // Validation custom error messages
        $messages = [
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a numeric value.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'name.required' => 'The name field is required.',
            'image.file' => 'The uploaded file must be a valid file.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must be at most 10 megabytes.',
            'campaign_id.required' => 'The campaign field is required.',
        ];

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate a unique user_id with 10 numeric digits
        $user_id = mt_rand(1000000000, 9999999999);
        $phone_number = $request->input('phone_number') ?? null;
        $name = $request->input('name') ?? null;
        // Generate the Gravatar URL
        $gravatarUrl = Avatar::create($request->email_address)->toBase64();
        $gravatarUrl = Avatar::create($request->email_address)
            ->toGravatar(['d' => 'identicon', 'r' => 'pg', 's' => 100]);


        // valide password only if provided or generate one
        $password = "";
        if ($request->filled('password')) {
            // Validation rules
            $rules = [
                'password' => ['required', 'confirmed'],
            ];
            // Validation custom error messages
            $messages = [
                'password.required' => 'The password field is required.',
                'password.confirmed' => 'The password confirmation does not match.',
            ];

            // Run the validation
            $credentials = Validator::make($request->all(), $rules, $messages);

            if ($credentials->fails()) {
                $errorMessage = $credentials->errors()->first();
                redirect()->back()->with(['success', $errorMessage]);
            }
            $password = $request->input('password');
        } else {
            $password = Str::random(8);
        }

        // Get File Extension
        $imagefullPathUrl = "";
        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();
            $subfolder = 'cdn/avatar/agents/'; // Generate Filename with Subfolder
            $filenametostore = $subfolder . Str::uuid() . time() . '.' . $extension;

            // Upload File to External Server (FTP)
            Storage::disk('ftp')->put($filenametostore, file_get_contents($request->file('image')->getRealPath()));
            // Helpers::uploadImageToFTP($filenametostore, $request->file('image'));

            // Get Full Path URL
            $basePath = "https://asset.kindgiving.org/"; // Replace with your actual base URL
            $imagefullPathUrl = $basePath . $filenametostore;
        } else {
            $imagefullPathUrl = $gravatarUrl; // Fix the key here ('avatar' instead of 'avatart')
        }

        // Save the user to the database
        $user = User::create([
            'user_id' => $user_id,
            'name' => $name,
            'phone_number' => $phone_number,
            'email' => $request->email_address,
            'role' => 'agent',
            'password' => Hash::make($password),
            'is_verified' => 'yes',
            'avatar' => $imagefullPathUrl,
            'status' => 'active',
        ]);

        // Update or insert into CampaignTeamUsers table
        CampaignTeamUsers::updateOrInsert(
            ['user_id' => $user_id],
            ['creator' => $campaign->manager_id, 'user_id' => $user_id]
        );

        $shortName = Helpers::getFirstName($user->name);
        $sms_content = "Hi $shortName, an account was created for you";
        $sms_content .= "Check you mail for more details.";
        $sms = new SMS($user->phone_number, $sms_content);
        $sms->singleSendSMS();


        $subject = "Account created successfully";
        Mail::to($user->email)->send(new AgentMail($subject, "n/a", $user, 'new'));
        //   return (new AgentMail($subject, "n/a", $user, 'new'))->render();

        return redirect()->back()->with('success', 'Agent added successfully, password ' . $password);
    }
    public function editAgentUser(Request $request)
    {
        // Retrieve the agent ID from the request 
        $agent_id = $request->id;
        // Retrieve campaign agents associated with the campaign
        $agent = User::where('user_id', $agent_id)
            ->first();

        // Check if the agent exists; if not, redirect back with an error message
        if (!$agent) {
            return back()->with('error', 'Agent not found');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:5'],
            'email_address' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($agent->user_id, 'user_id'),
            ],
            'phone_number' => [
                'required',
                'numeric',
                'digits:10',
                Rule::unique('users', 'phone_number')->ignore($agent->user_id, 'user_id'),
            ],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                File::image()->min('1kb')->max('10000kb'), // Increased max size to 10MB
            ],
            'password' => ['nullable', 'confirmed'],
        ], [
            'name.required' => 'The agent name is required.',
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a valid number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'image.file' => 'The uploaded file must be a valid file.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must be at most 10 megabytes.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update agent information
        $agent->name = $request->input('name');
        $agent->email = $request->input('email_address');
        $agent->phone_number = $request->input('phone_number');

        // Update password only if provided
        if ($request->filled('password')) {
            $agent->password = bcrypt($request->input('password'));
        }

        // Get File Extension
        $imagefullPathUrl = "";
        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();
            $subfolder = 'cdn/avatar/agents/'; // Generate Filename with Subfolder
            $filenametostore = $subfolder . Str::uuid() . time() . '.' . $extension;

            // Upload File to External Server (FTP)
            Storage::disk('ftp')->put($filenametostore, file_get_contents($request->file('image')->getRealPath()));
            // Helpers::uploadImageToFTP($filenametostore, $request->file('image'));

            // Get Full Path URL
            $basePath = "https://asset.kindgiving.org/"; // Replace with your actual base URL
            $imagefullPathUrl = $basePath . $filenametostore;
        } else {
            $imagefullPathUrl = $agent->avatar; // Fix the key here ('avatar' instead of 'avatart')
        }
        $agent->update([
            'avatar' => $imagefullPathUrl, // Fix the key here ('avatar' instead of 'avatart')
        ]);
        $agent->save();
        // Redirect to a success route
        return redirect()->back()->with('success', 'Agent updated successfully');
    }

    public function deleteAgentUser(Request $request)
    {
        // Retrieve the agent ID from the request 
        $agent_id = $request->id;
        // Retrieve campaign agents associated with the campaign
        $agent = CampaignAgent::with('user', 'donations')
            ->where('agent_id', $agent_id)
            ->first();
        if ($agent) {
            $agent->delete();
        }
        $agent = CampaignTeamUsers::with('user', 'campaign', 'creator')->where('user_id', $agent_id)
            ->first();

        $shortName = Helpers::getFirstName($agent->user->name);
        $sms_content = "Hi $shortName, your agent account was deleted on KindGiving";
        $sms = new SMS($agent->user->phone_number, $sms_content);
        $sms->singleSendSMS();

        $agent->delete();
        // Delete agent
        $agent->delete();
        $agent->user->delete();
        // Redirect to a success route
        return redirect(route('admin.all-users'))->with('success', 'Agent deleted successfully');
    }

    public function commission(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $commissions = Commission::with('campaign')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('campaign_id', 'like', "%$keyword%")
                    ->orWhere('commission', 'like', "%$keyword%")
                    ->orWhere('user_id', 'like', "%$keyword%");

                // Add whereHas to search within the related campaign
                $query->orWhereHas('campaign', function ($campaignQuery) use ($keyword) {
                    $campaignQuery->where('name', 'like', "%$keyword%")
                        ->orWhere('description', 'like', "%$keyword%");
                    // Add more fields if needed
                });
            })
            ->latest()
            ->paginate(50);
        //  dd($commissions->campaign);
        return view('admin.campaigns.commissions', compact('commissions', 'keyword', 'status'));
    }

    public function createOrUpdateCommission(Request $request)
    {
        $campaignId = $request->campaign_id;
        $commissionValue = $request->commission;



        // Check if the action is 'update'
        if ($request->action === 'update') {
            // Retrieve the campaign
            $campaign = Campaign::where('campaign_id', $campaignId)->first();

            if (!$campaign) {
                return back()->with('error', 'Campaign not found');
            }
            // Validate the request
            $validator = Validator::make($request->all(), [
                'commission1' => ['required', 'numeric'],
                'campaign_id' => ['required'],
            ], [
                // 'manager_id.required' => 'The manager_id is required.',
                'commission1.required' => 'The commission is required.',
                'commission1.numeric' => 'The commission must be a numeric value',
                'campaign_id.required' => 'The campaign id is required'
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }


            $campaignCommission = Commission::where('campaign_id', $campaignId)->first();

            if (!$campaignCommission) {
                return back()->with('error', 'Commission not found');
            }

            // Update commission
            $campaignCommission->commission = $request->input('commission1');
            $campaignCommission->save();
            return back()->with('success', $campaign->name . ' Commission updated successfully');

        } elseif ($request->action === 'create') {
            // Retrieve the campaign
            $campaignId = $request->campaign;

            $campaign = Campaign::where('campaign_id', $campaignId)->first();

            if (!$campaign) {
                return back()->with('error', 'Campaign not found');
            }

            // Check if the commission already exists
            $existingCommission = Commission::where('campaign_id', $campaignId)->first();
            if ($existingCommission) {
                return back()->with('error', 'Commission already exists');
            }
            // Validate the request
            $validator = Validator::make($request->all(), [
                'new_commission' => ['required', 'numeric'],
                'campaign' => ['required'],
            ], [
                // 'manager_id.required' => 'The manager_id is required.',
                'new_commission.required' => 'The commission is required.',
                'new_commission.numeric' => 'The commission must be a numeric value',
                'campaign.required' => 'The campaign id is required'
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }



            // Create a new commission
            Commission::create([
                'campaign_id' => $campaignId,
                'user_id' => $campaign->manager_id,
                'commission' => $request->input('new_commission')
            ]);

            return back()->with('success', 'Commission created successfully');
        } elseif ($request->action === 'delete') {
            $campaignId = $request->campaign;
            // Retrieve the campaign
            $campaign = Campaign::where('campaign_id', $campaignId)->first();

            if (!$campaign) {
                return back()->with('error', 'Campaign not found');
            }

            // Check if the commission already exists
            $existingCommission = Commission::where('campaign_id', $campaignId)->first();
            if (!$existingCommission) {
                return back()->with('error', 'Commission not found');
            }

            // Delete the commission
            $existingCommission->delete();

            return back()->with('success', 'Commission deleted successfully');
        } else {
            return back()->with('error', 'Invalid action');
        }
    }
}
