<?php

namespace App\Http\Controllers\Dashboard\Fundraiser;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Exports\DonationsExport;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Campaigns\{CampaignAgent, CampaignTeamUsers, Commission, FundraiserAccount, Prayer, SelectedCampaign};
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
use App\Models\UserAccountNotification;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AgentManagement extends Controller
{
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

    public function campaignAgents(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
            // 'status' => 'nullable|in:active,inactive',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        $keyword = $request->input('search');
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
        return view('pages.fundraiser.campaigns.agents', compact('campaign', 'agents', 'status', 'keyword'));
    }

    public function viewAgent(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
        $agent_id = $request->id;
        $keyword = $request->input('search');

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
        return view('manager.campaigns.view-agent', compact('campaign', 'agent', 'donations', 'keyword'));
    }


    public function editAgent(Request $request)
    {
        // Retrieve the campaign ID and agent ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
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

    public
    function deleteAgent(Request $request)
    {
        // Retrieve the campaign ID and agent ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
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
        return redirect(route('manager.campaign-agents', [$campaign_id]))->with('success', 'Agent deleted successfully');
    }


    public
    function users(Request $request)
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
        $agents = CampaignTeamUsers::with('user')->where('creator', auth()->user()->user_id)
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

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('manager.users.index', compact('agents', 'status', 'keyword'));
    }


    public
    function viewUser(Request $request)
    {
        // Retrieve the campaign ID from the request
        $agent_id = $request->id;
        $keyword = $request->input('keyword');

        $allCampaigns = Campaign::where('manager_id', auth()->user()->user_id)
            ->latest()
            ->paginate(50) ?: [];

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
        return view('manager.users.view')
            ->with('agent', $agent)
            ->with('donations', $donations)
            ->with('keyword', $keyword)
            ->with('allCampaigns', $allCampaigns)
            ->with('agentCampaigns', $agentCampaigns);
    }


    public
    function assignUserToCampaign(Request $request)
    {

        $campaign_id = $this->getCampaign()->campaign_id;
        $agent_id = $request->id;

        $campaign = Campaign::where('campaign_id', $campaign_id)->first();
        $agent = CampaignTeamUsers::with('user')->where('user_id', $agent_id)->first();

        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        if ($campaign->status != 'approved') {
            return back()->with('error', 'Campaign not approved');
        }

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

    public
    function addAgentUser(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'email_address' => ['required', 'email', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'digits:10', Rule::unique('users', 'phone_number')],
            'password' => ['nullable', 'confirmed'],
            'name' => ['required', 'string', 'min:5'],
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
        ];

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }
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
            ['creator' => auth()->user()->user_id, 'user_id' => $user_id]
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

    public
    function editAgentUser(Request $request)
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

    public
    function deleteAgentUser(Request $request)
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
        return redirect(route('manager.all-users'))->with('success', 'Agent deleted successfully');
    }
}
