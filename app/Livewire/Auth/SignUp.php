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
use Illuminate\Support\Facades\Mail;

class SignUp extends Component
{
 public $name, $email, $password, $phone;
 public $errorMessage;

 public function rules()
 {
  return [
   'name' => 'required|min:5|string',
   'email' => ['required', 'email', Rule::unique('users', 'email')],
   'phone' => ['required', 'numeric', Rule::unique('users', 'phone_number')],
   'password' => 'required',
  ];
 }

 public function messages()
 {
  return [
   'email.required' => 'The email address field is required.',
   'email.email' => 'The email address must be a valid email address.',
   'phone.required' => 'The phone number field is required.',
   'phone.numeric' => 'The phone number must be a numeric value.',
   'password.required' => 'The password field is required.',
   'email.unique' => 'The email address has already been taken.',
   'phone.unique' => 'The phone number has already been taken.',
  ];
 }

 public function createAccount(Request $request)
 {
  $this->validate();

  // Generate a unique user_id with 10 numeric digits
  $user_id = mt_rand(1000000000, 9999999999);
  // Check if the generated user ID already exists
  while (User::where('user_id', $user_id)->exists()) {
   $user_id = mt_rand(1000000000, 9999999999);
  }
  $this->phone = str_replace('+', '', $this->phone);

  // Generate the Gravatar URL
  $avatar = $this->avatar();
  try {
   // Save the user to the database
   $user = User::create([
    'user_id' => $user_id,
    'name' => $this->name,
    'phone_number' => $this->phone,
    'email' => $this->email,
    'role' => 'campaign_manager',
    'password' => Hash::make($this->password),
    'is_verified' => 'no',
    'avatar' => $avatar
   ]);
  } catch (\Exception $e) {
   return $this->errorMessage = $e->getMessage();
  }
  $subject = 'KindGiving Account Verification';


  $user = User::where('user_id', 3518691972)->first();
  // Generate a random OTP for verification
  $otp = mt_rand(000000, 999999);
  // Generate a token for the user
  $token = Str::random(75);

  // Save the OTP to the database
  VerificationOTP::create([
   'user_id' => $user_id,
   'otp' => $otp,
   'token' => $token,
   'expires_at' => now()->addMinutes(10), // Adjust the expiration time as needed
  ]);
  try {
   // Send the OTP to the user's phone number 
   $sms_content = "Your Kind Giving OTP code is $otp, valid for 10 minutes.";
   $sms = new SMS($this->phone, $sms_content);
   // $sms->singleSendSMS();

   // Send the OTP to the user's email
   Mail::to($user->email)->send(new AuthenticationMail($subject, $user, $otp, 'signup'));

  } catch (\Exception $e) {

  }

  return redirect()->route('verify-otp', ['token' => $token]);
 }



 private function avatar()
 {
  // Randomly pick an avatart from the array
  $avatars = [
   asset('assets/images/avatar/astronaut.png'),
   asset('assets/images/avatar/bear.png'),
   asset('assets/images/avatar/cat.png'),
   asset('assets/images/avatar/chicken.png'),
   asset('assets/images/avatar/dog.png'),
   asset('assets/images/avatar/panda.png'),
   asset('assets/images/avatar/rabbit.png'),

  ];
  return $avatars[array_rand($avatars)];
 }
 public function render(Request $request)
 {

  return view('livewire.auth.sign-up');
 }

}
