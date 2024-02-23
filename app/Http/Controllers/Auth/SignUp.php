<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Models\User;
use App\Models\VerificationOTP;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Utilities\VerifyUserName;
use Laravolt\Avatar\Facade as Avatar;

class SignUp extends Model
{
    use HasFactory;

    public function show()
    {
        return view('pages.auth.sign-up');
    }

    public function processRegistration(Request $request)
    {
        // Validation rules
        $rules = [
            'email_address' => ['required', 'email', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'numeric', Rule::unique('users', 'phone_number')],
            'password' => ['required', 'confirmed'],
        ];

        // Validation custom error messages
        $messages = [
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a numeric value.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }



        // Generate a unique user_id with 10 numeric digits
        $user_id = mt_rand(1000000000, 9999999999);
        $phone_number = $request->input('phone_number') ?? null;

        // Verify account name
        $accountNameResult = new VerifyUserName($phone_number);
        $accountNameResponse = $accountNameResult->getAccountName();
        if (isset($accountNameResponse['success']) && $accountNameResponse['success'] === false) {
            // If $accountName is null or success is false, consider it as an invalid phone number
            return response()->json(['success' => false, 'message' => "Invalid phone number."]);
        }

        // Generate the Gravatar URL
        $gravatarUrl = Avatar::create($request->email_address)->toBase64();
        $gravatarUrl = Avatar::create($request->email_address)->toGravatar(['d' => 'identicon', 'r' => 'pg', 's' => 100]);

        // Save the user to the database
        $user = User::create([
            'user_id' => $user_id,
            'name' => $accountNameResponse['message'],
            'phone_number' => $phone_number,
            'email' => $request->email_address,
            'role' => 'campaign_manager',
            'password' => Hash::make($request->password),
            'is_verified' => 'no',
            'avatar' => $gravatarUrl
        ]);

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

        //send otp to user
        $sms_content = "Your Kind Giving OTP code is $otp, valid for 10 minutes.";
        $sms = new SMS($phone_number, $sms_content);
        $sms->singleSendSMS(); 
        return response()->json(['success' => true, 'token' => $token]);
    }
}
