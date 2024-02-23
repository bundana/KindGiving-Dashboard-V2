<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Mail\UserEmailVerification;
use App\Models\ReferralProgram;
use App\Models\User;
use App\Models\VerificationOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Check if the user exists
        if (!$user) {
            return back()->with('error', 'You are not allowed to perform this action');
        }
        if ($request->has('profile_update')) {

            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => ['nullable', 'min:5'],
                'email_address' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
                ],
                'phone_number' => [
                    'required',
                    'numeric',
                    'digits:10',
                    Rule::unique('users', 'phone_number')->ignore($user->user_id, 'user_id'),
                ],
                'image' => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpeg,png,jpg,gif,svg',
                    File::image()->min('1kb')->max('10000kb'), // Increased max size to 10MB
                ],
            ], [
                'name.required' => 'The name field is required.',
                'name.min' => 'The name must be at least 5 characters.',
                'phone_number.required' => 'The phone number field is required.',
                'phone_number.numeric' => 'The phone number must be numeric.',
                'phone_number.digits' => 'The phone number must be 10 digits.',
                'phone_number.unique' => 'The phone number has already been taken.',
                'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
                'image.min' => 'The image must be at least 1 kilobyte.',
                'image.max' => 'The image may not be greater than 10000 kilobytes.',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Update user profile
            if ($request->input('name') == null) {
                $phone_number = $request->input('phone_number') ?? null;
                $accountNameResult = new VerifyUserName($phone_number);
                $accountNameResponse = $accountNameResult->getAccountName();

                if (isset($accountNameResponse['success']) && $accountNameResponse['success'] === false) {
                    return back()->with('error', 'Invalid phone number');
                }
                $userName = $accountNameResponse['message'];
            } else {
                $userName = $request->input('name');
            }
            $verifyMessage = '';
            if ($request->input('email_address') != $user->email) {
                // Generate a token for the user
                $token = Str::random(75);
                $otp = mt_rand(000000, 999999);
                // Save the OTP to the database
                VerificationOTP::create([
                    'user_id' => $user->user_id,
                    'otp' => $otp,
                    'token' => $token,
                    'expires_at' => now()->addMinutes(10), // Adjust the expiration time as needed
                ]);

                // Send email verification and sms
                $link = route('verify-email', $token);
                $subject = "Kind Giving Email Verification";
                Mail::to($request->input('email_address'))->send(new UserEmailVerification($subject, $link));
                $verifyMessage = "A verification link has been sent to your new email address.";
                //send otp to user
                $sms_content = "Click this link to verify your email $link";
                $sms = new SMS($user->phone, $sms_content);
                $sms->singleSendSMS();
                 // Update user profile directly using the update method
            $affected = User::where('user_id', $user->user_id)
            ->update([
                'is_verified' => "no", 
            ]);
            }

            // Update user profile directly using the update method
            $affected = User::where('user_id', $user->user_id)
                ->update([
                    'phone_number' => $request->input('phone_number'),
                    'name' => $userName,
                    'email' => $request->input('email_address'),
                ]);

            if ($affected) {
                return back()->with('success', 'Profile updated successfully. Email verification sent. ' . $verifyMessage);
            } else {
                return back()->with('error', 'Something went wrong');
            }
        } elseif ($request->has('password_update')) {


            // Verify the current password
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect']);
            }

            // Validate the request for the new password and confirmation
            $validator = Validator::make($request->all(), [
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Update the user's password  
            $affected = $user->update(
                ['password' => bcrypt($request->password)]
            );
            Auth::logout();
            //send otp to user
            $sms_content = "Password changed for your Kind Giving account. Contact support if not initiated by you.";
            $sms = new SMS($user->phone_number, $sms_content);
            $sms->singleSendSMS();
            if ($affected) {
                // Add a success message to the session 
                return redirect(route('login'))->with('success', 'Password updated successfully, Proceed to login');
            } else {
                return back()->with('error', 'Something went wrong');
            }
        }
    }

    public function referralProgram(Request $request)
    {
        $user = Auth::user();
        $referral_code = 'KG-' . strtoupper(Str::random(4));

        $referral_link = 'https://kindgiving.org/refer/' . $referral_code;
        $affected =  ReferralProgram::updateOrInsert(
            ['agent_id' => $user->user_id],
            [
                'agent_id' => $user->user_id,
                'referral_code' => $referral_code,
                'link' => $referral_link
            ]
        );

        if ($affected) {
            return back()->with('success', 'Referral code generated successfully');
        } else {
            return back()->with('error', 'Something went wrong');
        }
    }
}
