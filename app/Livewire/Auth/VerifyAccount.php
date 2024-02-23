<?php

namespace App\Livewire\Auth;

use App\Http\Controllers\Utilities\Helpers;
use App\Models\Campaigns\Campaign;
use App\Models\Campaigns\Category;
use App\Models\Campaigns\Donations as CampaignDonation;
use App\Models\Campaigns\SelectedCampaign;
use App\Models\VerificationOTP;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Livewire;
use Livewire\Attributes\Layout;
use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Utilities\VerifyUserName;
use App\Mail\AuthenticationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class VerifyAccount extends Component
{
 public $otp;
 public $errorMessage, $successMessage;
 public $user, $tokenData;

 public function rules()
 {
  return [
   'otp' => 'required|digits:6|',
  ];
 }

 public function messages()
 {
  return [
   'otp.required' => 'OTP is required',
   'otp.digits' => 'OTP must be at least 6 digits',

  ];
 }

 public function verifyAccount(Request $request)
 {
  $this->validate();

  // Get token from the database
  $getToken = VerificationOTP::where('token', $this->tokenData->token)->first();
  if (!$getToken) {
   return $this->errorMessage = 'Invalid token or token has expired';
  }

  $user = User::where('user_id', $getToken->user_id)->first();
  if (!$user) {
   return $this->errorMessage = 'Something went wrong, please try again later.';
  }
 
  if ($getToken->otp != $this->otp) {
   return $this->errorMessage = 'Invalid OTP code entered, please try again';
  }
  $this->errorMessage = '';
  $this->successMessage = ''; 

  // Update user account
  $updateUser = User::where('user_id', $getToken->user_id)->update([
   'is_verified' => 'yes'
  ]);

  if (!$updateUser) {
   return response()->json(['success' => false, 'message' => 'Unable to update user account, try again']);
  }

  // Log in the user
  Auth::login($user);

  // Delete token
  $getToken->delete();
  return redirect()->route('login')->with('success', 'Account verified successfully'); 
 }


 public function resendOTP(Request $request)
 {
  // Get token from the database
  $getToken = VerificationOTP::where('token', $this->tokenData->token)->first();
  if (!$getToken) {
   return $this->errorMessage = 'Invalid token or token has expired';
  }

  $user = User::where('user_id', $getToken->user_id)->first();
  if (!$user) {
   return $this->errorMessage = 'Something went wrong, please try again later.';
  }


  // Generate a random OTP for verification
  $otp = $otp = mt_rand(000000, 999999);
  // Generate a token for the user
  $newToken = Str::random(75);

  // // Save the OTP to the database
  VerificationOTP::updateOrInsert(
   ['user_id' => $user->user_id, 'token' => $this->tokenData->token],
   [
    'user_id' => $user->user_id,
    'otp' => $otp,
    'expires_at' => now()->addMinutes(10)
   ]
  );

  //send otp to user
  $sms_content = "Your new Kind Giving OTP code is $otp, valid for 10 minutes.";
  $sms = new SMS($user->phone_number, $sms_content);
  // $sms->singleSendSMS();
  return $this->successMessage = 'New OTP Code Sent Successfully';
 }

 public function render(Request $request)
 {

  return view('livewire.auth.verify-account');
 }

}
