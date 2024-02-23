<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Utilities\Messaging\SMS;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function index(Request $request)
    {
        return view('auth.forget-password');
    }

    public function processForgetPassword(Request $request)
    {
        // Validation rules
        $rules = [
            'phone_number' => ['required'],
        ];

        // Validation custom error messages
        $messages = [
            'phone_number.required' => 'Phone Number is required.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid user']);
        }
        // Generate a random OTP for verification
        $otp = mt_rand(100000, 999999);
        // Generate a token for the user
        $token = Str::random(75);

        // Save the OTP to the database
        PasswordReset::updateOrInsert(
            ['user_id' => $user->user_id],
            [
                'user_id' => $user->user_id,
                'otp' => $otp,
                'token' => $token,
                'email' => $user->email,
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now()
            ]
        );

        //send otp to user
        $sms_content = "Your Kind Giving Password rest OTP code is $otp, valid for 10 minutes.";
        $sms = new SMS($request->phone_number, $sms_content);
        // $sms->singleSendSMS();
        return response()->json([
            'success' => true,
            'message' => 'Reset OTP sent successfully',
            'redirectedPage' => route('reset-password', [$token])
        ]);
    }
    public function resetPasswordIndex(Request $request)
    {
        $token = $request->route('token');

        // Get token from the database
        $getToken = PasswordReset::where('token', $token)->first();
        if (!$getToken) {
            return redirect()->route('login')->with('error', 'Invalid Password reset token');
        }
        $user = User::where('user_id', $getToken->user_id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid user']);
        }

        return view('auth.password-reset')->with('tokenData', $getToken)->with('user', $user);
    }

    public function processResetPassword(Request $request)
    {
        $token = $request->route('token');

        // Get token from the database
        $getToken = PasswordReset::where('token', $token)->first();
        if (!$getToken) {
            return redirect()->route('login')->with('error', 'Invalid Password reset token');
        }

        // Validation rules
        $rules = [
            'otp_code' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];

        // Validation custom error messages
        $messages = [
            'otp.required' => 'The OTP field is required.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }

        // Check if the provided OTP matches
        if ($getToken->otp != $request->otp_code) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code']);
        }
        $user = User::where('user_id', $getToken->user_id)->first();
        // Check if the user is found
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        // Update the user's password
        $user->password = bcrypt($request->password);
        $user->save();

        // Delete the password reset record
        $getToken->delete();

        //send otp to user
        $sms_content = "Your Kind Giving Account password was changed successfully, contact support of this was you.";
        $sms = new SMS($user->phone_number, $sms_content);
        $sms->singleSendSMS();

        // Redirect to login or any other page
        return response()->json(['success' => true, 'message' => 'Password reset successfully', 'redirectedPage' => route('login')]);
    }
}
