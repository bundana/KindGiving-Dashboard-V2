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

class Login extends Component
{
 public $email_phone, $password;
 public $errorMessage;

 public function rules()
 {
  return [
   'email_phone' => 'required|min:5|string',
   'password' => 'required',
  ];
 }

 public function messages()
 {
  return [
   'email_phone.required' => 'The phone number or email field is required.',
   'password.required' => 'The password field is required.',
  ];
 }

 public function signIn(Request $request)
 {
  $this->validate();

  // Check if the input is a valid email
  $isEmail = filter_var($this->email_phone, FILTER_VALIDATE_EMAIL);

  // Find the user by email or phone number
  $user = $isEmail
   ? User::where('email', $this->email_phone)->first()
   : User::where('phone_number', $this->email_phone)->first();
  // Check if the user exists

  if (!$user) {
   return $this->errorMessage = 'User does not exist with this ' . ($isEmail ? 'email' : 'phone number');
  }

  // Attempt to authenticate the user with "Remember Me" functionality
  if (Auth::attempt([$isEmail ? 'email' : 'phone_number' => $this->email_phone, 'password' => $this->password, 'is_verified' => 'yes'], $request->has('remember'))) {
   $request->session()->regenerate();

   // Log in the user 
   // Retrieve the intended URL from the session or use a default page
   $redirectedPage = $request->session()->get('requested_url');
   if (!$redirectedPage) {
    // Set a default page based on the user's role
    switch ($user->role) {
     case "agent":
      $redirectedPage = route('agent.index');
      break;
     case "admin":
      $redirectedPage = route('admin.index');
      break;
     case "dev":
      $redirectedPage = route('admin.index');
      break;
     case "campaign_manager":
      $redirectedPage = route('manager.index');
      break;
    }
   }

   // Clear the session key
   $request->session()->pull('requested_url');
   return redirect("$redirectedPage")->route('verify-otp', ['token' => 'Login Successfully']);
  }else{
   return $this->errorMessage = 'Invalid login credentials';
  
  }
 }



 public function render(Request $request)
 {

  return view('livewire.auth.login');
 }

}
