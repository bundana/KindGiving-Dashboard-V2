<?php

namespace App\Http\Controllers\Dashboard\Fundraiser;

use Bundana\Services\Messaging\Mnotify;
use Illuminate\Support\Facades\Auth;
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

class DonationManagement extends Controller
{

    private static function getCampaign()
    {
        $user = auth()->user();
        $campaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$campaign) {
            $campaign = Campaign::where('manager_id', $user->user_id)->first();
        }
        return $campaign;
    }

    public function allDonations(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

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

        $fundraiserAccount = FundraiserAccount::where('campaign_id', $campaign->campaign_id)->first() ?: [];
        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('pages.fundraiser.campaigns.donors', compact('campaign', 'fundraiserAccount', 'donations', 'status', 'keyword'));
    }


    public function newDonationsReceiptIndex(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('pages.fundraiser.campaigns.new-receipt')->with('campaign', $campaign);
    }

    public function createReceipt(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'digits:10'],
            'amount' => ['required', 'numeric'],
            'full_name' => ['required', 'string'],
            'message' => ['nullable', 'string']
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a valid amount.',
            'phone.required' => 'The phone number field is required.',
            'phone.digits' => 'The momo number must be exactly 10 digits.',
            'full_name.required' => 'The Donor name field is required.',
            'message.string' => 'The message field must be a valid message'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $campaign_id = $this->getCampaign()->campaign_id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donarName = $request->input('full_name');
        $amount = $request->input('amount');
        $momoNumber = $request->input('phone');

        $transRef = str::random(8);
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
                'comment' => $request->input('message'),
                'status' => 'unpaid',
            ]
        );

        UserAccountNotification::create([
            'user_id' => auth()->user()->user_id,
            'type' => 'campaign',
            'title' => 'Donation Receipt Created',
            'message' => "Donation receipt created for $momoNumber ($donarName) of GHS $amount. #$transRef, proceed to payment"
        ]);

        //send sms notices
        $shortName = Helpers::getFirstName($donarName);
        $sms_content = "Dear $shortName, thank you for the GHS $amount donation to {$campaign->name}.";
        $sms_content .= "Your reference: $transRef";
        $sms_content .= "God bless you.";
//        Mnotify::to($request->input('phone'))->message($sms_content)->send();

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

    public function allReceipts(Request $request)
    {
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        // Render the 'view' template and pass the campaign, donations, and agents data
        return view('pages.fundraiser.campaigns.receipts', compact('campaign'));
    }

    public function payReceiptIndex(Request $request)
    {
        if (!session()->has('selected_donations')) {
            return redirect(route('manager.donation-receipts'))->with('error', 'No donations receipts selected');
        }
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        return view('pages.fundraiser.campaigns.pay-receipt', compact('campaign'));
    }

    public function payReceiptCheckout(Request $request)
    {
        if (!session()->has('selected_donations')) {
            return redirect(route('manager.donation-receipts'))->with('error', 'No donations receipts selected');
        }
        // Validate inputs
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donationsInsession = session()->has('selected_donations') ? session()->get('selected_donations') : [];

        $donations = []; // Initialize an array to store fetched donation data

        foreach ($donationsInsession as $donationRef) {
            $data = Donations::where('donation_ref', $donationRef)
                ->where('status', 'unpaid')
                ->first();

            if ($data) {
                // Store the fetched data in the array
                $donations[] = $data;
            }
        }

        $totalAmount = 0; // Initialize total amount outside the loop

        foreach ($donations as $donation) {
            // Add each donation amount to the totalAmount
            $totalAmount += $donation->amount;
        }

        return view('pages.fundraiser.campaigns.receipt-payment-page',
            compact('campaign', 'donations', 'totalAmount'));
    }

    public function payReceiptsForm(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }

        $donations = session()->has('selected_donations') ? session()->get('selected_donations') : [];

        // Store donation references in an array
        $donationReferences = [];

        foreach ($donations as $donationRef) {
            $data = Donations::where('donation_ref', $donationRef)
                ->where('status', 'unpaid')
                ->first();

            if ($data) {
                // Store only the donation reference in the array
                $donationReferences[] = $donationRef;
            }
        }

        $totalAmount = 0; // Initialize total amount outside the loop

        // Calculate total amount based on donation references
        foreach ($donationReferences as $donationRef) {
            $data = Donations::where('donation_ref', $donationRef)
                ->where('status', 'unpaid')
                ->first();

            if ($data) {
                $totalAmount += $data->amount;
            }
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


    public function viewDonationsStats(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;
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
        $donations = Donations::with('user')->where('creator', auth()->user()->user_id)->when($method, function ($query) use ($method) {
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
            'pages.fundraiser.campaigns.all-donations-reports',
            compact('donations', 'keyword', 'campaign', 'date_range', 'method')
        );
    }

    public function exportDonations(Request $request)
    {
        $campaign_id = $request->input('campaign_id');
        $keyword = $request->input('keyword');
        $date_range = $request->input('date_range');

        // Instantiate DonationsExport with the request object
        $export = new DonationsExport($request, $campaign_id, $keyword, $date_range);
        // Download the Excel file
        return Excel::download($export, 'donations-report.xlsx');
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


    public function paymentLinksForm(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('pages.fundraiser.campaigns.payment-link-form')->with('campaign', $campaign);
    }

    public function directPaymentLinkForm(Request $request)
    {
        // Retrieve the campaign ID from the request
        $campaign_id = $this->getCampaign()->campaign_id;

        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaign_id)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        return view('pages.fundraiser.campaigns.direct-payment-link-form')->with('campaign', $campaign);
    }
}
