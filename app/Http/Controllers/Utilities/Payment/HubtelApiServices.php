<?php

namespace App\Http\Controllers\Utilities\Payment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utilities\Helpers;
use App\Models\Campaigns\SelectedCampaign;
use Bundana\Services\Messaging\Mnotify;
use App\Http\Controllers\Utilities\Messaging\{SMS, WhatsApp};
use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\Commission;
use App\Models\Campaigns\Donations;
use App\Models\Campaigns\FundraiserAccount;
use App\Models\UnpaidDonationsReceipts;
use App\Models\User;
use App\Models\UserAccountNotification;
use Attribute;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HubtelApiServices extends Controller
{
    private const CALLBACKURL = 'https://api.kindgiving.org/v1/payments/webhooks/hubtel/online-checkout';
    public $totalAmount, $description, $callbackUrl, $returnUrl, $cancellationUrl, $clientReference;

    private static function getCampaign()
    {
        $user = auth()->user();
        $campaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$campaign) {
            $campaign = Campaign::where('manager_id', $user->user_id)->first();
        }
        return $campaign;
    }

    public function generateInvoice($totalAmount, $description, $callbackUrl, $returnUrl, $cancellationUrl)
    {
        $this->totalAmount = $totalAmount;
        $this->description = $description;
        $this->callbackUrl = $callbackUrl;
        $this->returnUrl = $returnUrl;
        $this->cancellationUrl = $cancellationUrl;
        $this->clientReference = Str::random(8);

        // Instantiate the Hubtel class with your parameters
        $hubtel = new Hubtel($totalAmount, $description, $callbackUrl, $returnUrl, $cancellationUrl, $this->clientReference);

        // Call the initiate method to make the API request
        $response = $hubtel->initiate();
        // Decode the JSON response
        $responseArray = json_decode($response, true);

        // Check the status and handle the response accordingly
        $checkoutDirectUrl = '';
        $checkoutUrl = '';

        if ($responseArray['status']) {
            // Success case
            $responseData = $responseArray['data'];
            $clientReference = $responseData['clientReference'];
            $checkoutUrl = $responseData['checkoutUrl'];
            $checkoutId = $responseData['checkoutId'];
            $checkoutDirectUrl = $responseData['checkoutDirectUrl'];

            return json_encode(['checkoutUrl' => $checkoutUrl, 'checkoutId' => $checkoutId, 'checkoutDirectUrl' => $checkoutDirectUrl, 'clientReference' => $clientReference]);
        } else {
            // Error case
            $errorData = isset($responseArray['error']) ? $responseArray['error'] : 'Unknown error';
            redirect()->back()->with('error', "Hubtel $errorData:: Something went wrong. Please try again later.");
        }
    }

    public function handleFormSubmit(Request $request)
    {
        $user = Auth::user();
        $campaignId = $this->getCampaign()->campaign_id;

        // Find campaign
        $campaign = Campaign::where('campaign_id', $campaignId)->first();

        if (!$campaign) {
            return redirect()->back()->with('error', 'Campaign not found');
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

        // Assuming $totalAmount is calculated correctly
        $description = "Payment of donation receipts of ₵$totalAmount for $campaign->title";
        $callbackUrl = self::CALLBACKURL;

        $returnUrl = route("manager.all-donation-receipts", [$campaignId]);
        $cancellationUrl = $returnUrl;


        $res = $this->generateInvoice($totalAmount, $description, $callbackUrl, $returnUrl, $cancellationUrl);

        if ($res) {
            $res = json_decode($res, true);
            $checkoutUrl = $res['checkoutUrl'];
            $checkoutId = $res['checkoutId'];
            $checkoutDirectUrl = $res['checkoutDirectUrl'];
            $clientReference = $res['clientReference'];
            $donationReferencesJson = json_encode($donationReferences, true);

            // Assuming $donationReferences is not empty
            UnpaidDonationsReceipts::updateOrInsert(
                ['user_id' => $user->user_id, 'reference' => $clientReference, 'campaign_id' => $campaignId],
                ['user_id' => $user->user_id, 'data' => $donationReferencesJson, 'amount' => $totalAmount, 'type' => 'receipt', 'phone' => $user->phone_number]
            );


            return redirect($checkoutUrl);
        } else {
            return redirect()->back()->with('error', 'Hubtel:: Something went wrong. Please try again later.');
        }
    }

    public function handleDirectPaymentLinkFormSubmit(Request $request)
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

        $campaignId = $this->getCampaign()->campaign_id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaignId)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        $user = Auth::user();

        if (!$user) {
            return back()->with('error', 'User not found');
        }

        $donarName = $request->input('full_name');
        $amount = $request->input('amount');
        $momoNumber = $request->input('phone');

        $transRef = str::random(8);

        Donations::create(
            [
                'creator' => $campaign->manager_id,
                'campaign_id' => $campaignId,
                'donation_ref' => $transRef,
                'momo_number' => $momoNumber,
                'amount' => $amount,
                'donor_name' => $donarName,
                'method' => 'web',
                'agent_id' => $user->user_id,
                'status' => 'unpaid'
            ]
        );

        UserAccountNotification::create([
            'user_id' => auth()->user()->user_id,
            'type' => 'campaign',
            'title' => 'Payment Link generated for ',
            'message' => "$momoNumber ($donarName) of GHS $amount. #$transRef, proceed to payment"
        ]);


        $description = "Payment Link generated for $donarName of {$campaign->name}";

        $returnUrl = "http://kindgiving.org/campaigns/{$campaign->campaign_id}";
        $cancellationUrl = "http://kindgiving.org/campaigns/{$campaign->campaign_id}";

        $res = $this->generateInvoice($amount, $description, self::CALLBACKURL, $returnUrl, $cancellationUrl);

        if (!$res) {
            return redirect()->back()->with('error', 'Hubtel:: Something went wrong. Please try again later.');
        }
        $userPhone = $request->input('phone');
        $donationReferences[] = $transRef;
        $res = json_decode($res, true);
        $checkoutUrl = $res['checkoutUrl'];
        $checkoutId = $res['checkoutId'];
        $checkoutDirectUrl = $res['checkoutDirectUrl'];
        $clientReference = $res['clientReference'];
        $donationReferencesJson = json_encode($donationReferences, true);

        // Assuming $donationReferences is not empty
        UnpaidDonationsReceipts::updateOrInsert(
            ['user_id' => $user->user_id, 'reference' => $clientReference, 'campaign_id' => $campaignId],
            ['user_id' => $user->user_id, 'data' => $donationReferencesJson, 'amount' => $amount, 'type' => 'direct', 'phone' => $userPhone]
        );


        $link = $checkoutUrl;
        $link = Helpers::generateShortUrl($link);

        $shortName = Helpers::getFirstName($donarName);

        $sms_content = "Hi $shortName, Click $link to complete your generous donation of GHS $amount";
        $sms_content .= "Ref: $transRef";
        $sms_content .= "Your support means a lot!";
        // $sms = new SMS($userPhone, $sms_content);

        // send success message
        return redirect($checkoutUrl);
    }


    public function generatePaymentLink(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'digits:10'],
            'amount' => ['required', 'numeric'],
            'full_name' => ['required', 'string'],
            'message' => ['nullable', 'string'],
            'channel' => ['required', 'in:sms,whatsapp']
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a valid amount.',
            'phone.required' => 'The phone number field is required.',
            'phone.digits' => 'The momo number must be exactly 10 digits.',
            'full_name.required' => 'The Donor name field is required.',
            'message.string' => 'The message field must be a valid message',
            'channel.required' => 'The channel is required',
            'channel.in' => 'The channel must be either sms or whatsapp'
        ]);


        // Check if validation fails
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $campaignId = $this->getCampaign()->campaign_id;
        // Find the campaign with the given ID
        $campaign = Campaign::where('campaign_id', $campaignId)->first();

        // Check if the campaign exists; if not, redirect back with an error message
        if (!$campaign) {
            return back()->with('error', 'Campaign not found');
        }
        $user = Auth::user();

        if (!$user) {
            return back()->with('error', 'User not found');
        }

        $donarName = $request->input('full_name');
        $amount = $request->input('amount');
        $momoNumber = $request->input('phone');

        $transRef = str::random(8);

        Donations::create(
            [
                'creator' => $campaign->manager_id,
                'campaign_id' => $campaignId,
                'donation_ref' => $transRef,
                'momo_number' => $momoNumber,
                'amount' => $amount,
                'donor_name' => $donarName,
                'method' => 'web',
                'agent_id' => $user->user_id,
                'status' => 'unpaid'
            ]
        );

        UserAccountNotification::create([
            'user_id' => auth()->user()->user_id,
            'type' => 'campaign',
            'title' => 'Payment Link generated for ',
            'message' => "$momoNumber ($donarName) of GHS $amount. #$transRef, proceed to payment"
        ]);


        $description = "Payment Link generated for $donarName of {$campaign->name}";

        $returnUrl = "http://kindgiving.org/campaigns/{$campaign->campaign_id}";
        $cancellationUrl = "http://kindgiving.org/campaigns/{$campaign->campaign_id}";

        $res = $this->generateInvoice($amount, $description, self::CALLBACKURL, $returnUrl, $cancellationUrl);
        // dd($res);
        if (!$res) {
            return redirect()->back()->with('error', 'Hubtel:: Something went wrong. Please try again later.');
        }

        $res = json_decode($res, true);
        $checkoutUrl = $res['checkoutUrl'];
        $checkoutId = $res['checkoutId'];
        $checkoutDirectUrl = $res['checkoutDirectUrl'];
        $clientReference = $res['clientReference'];
        $donationReferencesJson = json_encode($transRef, true);
        // Assuming $donationReferences is not empty
        UnpaidDonationsReceipts::updateOrInsert(
            ['user_id' => $user->user_id, 'reference' => $clientReference, 'campaign_id' => $campaignId],
            [
                'user_id' => $user->user_id,
                'data' => $donationReferencesJson,
                'amount' => $amount,
                'phone' => $momoNumber,
                'type' => 'direct'
            ]
        );

        $userPhone = $request->input('phone');
        $link = $checkoutUrl;
        $link = Helpers::generateShortUrl($link);

        $shortName = Helpers::getFirstName($donarName);
        $method = $request->input('channel');
        switch ($method) {
            case 'sms':
                //send sms notices
                $sms_content = "Hi $shortName, Click $link to complete your generous donation of GHS $amount";
                $sms_content .= "Ref: $transRef";
                $sms_content .= "Your support means a lot!";
                Mnotify::to($userPhone)->message($sms_content)->send();
                break;
            case 'whatsapp':
                $shareBody = Str::of(strip_tags($campaign->description))->limit(115);
                $shareTitle = $campaign->name;
                $shareLink = $campaign->short_url;
                $message = "Hello! $shortName, \n\nYour support is valued!\n\n";
                $message .= "Click the link below to complete your donation for *$shareTitle* on *kindGiving*\n\n";
                $message .= "*Payment Details:*\n";
                $message .= "• Link: $link \n";
                $message .= "• Amount: GH₵ {$amount}\n";
                $message .= "• Ref: $transRef \n \n";
                $message .= "Learn more about campaign at  $shareLink";

                return redirect()->away('https://wa.me/?text=' . urlencode($message));

            // $whatsapp = WhatsApp::to($userPhone)->message($message)->send();
        }
        // send success message
        return redirect()->back()->with('success', 'Payment link generated successfully ' . $link);
    }

}
