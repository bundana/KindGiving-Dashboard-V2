<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationOTP;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\Utilities\Messaging\SMS;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class VerifyAccount extends Controller
{
    public function show(Request $request)
    {
        $token = $request->route('token');

        // Get token from the database
        $getToken = VerificationOTP::where('token', $token)->first();
        if (!$getToken) {
            return redirect(route('login'))->with('error', 'Invalid token');
        }
        $user = User::where('user_id', $getToken->user_id)->first();
        if (!$user) {
            return redirect(route('login'))->with('error', 'User not found');
        }
        $tokenData = $getToken;
        return view('pages.auth.verify-account', compact('tokenData', 'user'));
    }

    public function processVerification(Request $request)
    {
        $token = $request->route('token');

        // Get token from the database
        $getToken = VerificationOTP::where('token', $token)->first();
        if (!$getToken) {
            return response()->json(['success' => false, 'message' => 'Invalid token']);
        }

        $user = User::where('user_id', $getToken->user_id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid user']);
        }

        // Validation rules
        $rules = [
            'otp_code' => ['required'],
        ];

        // Validation custom error messages
        $messages = [
            'otp_code.required' => 'OTP code is required.',
        ];

        // Run the validation
        $credentials = Validator::make($request->all(), $rules, $messages);

        if ($credentials->fails()) {
            $errorMessage = $credentials->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }

        if ($getToken->otp != $request->otp_code) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code']);
        }

        // // Check if token is expired
        // $now = time();
        // $tokenCreatedAt = strtotime($getToken->created_at);
        // $diff = $now - $tokenCreatedAt;
        // $diffInMinutes = round($diff / 60); // Fix the calculation

        // if ($diffInMinutes > 5) {
        //     return response()->json(['success' => false, 'message' => 'Token expired']);
        // }

        // Update user account
        $updateUser = User::where('user_id', $getToken->user_id)->update([
            'is_verified' => true
        ]);

        if (!$updateUser) {
            return response()->json(['success' => false, 'message' => 'Unable to update user account, try again']);
        }

        // Log in the user
        Auth::login($user);

        // Delete token
        $getToken->delete();
        return response()->json(['success' => true, 'message' => 'Account verified successfully', 'redirectedPage' => route('agent.index')]);

        // return redirect()->route('admin.index')->with('success', 'Account verified successfully', 'redirectedPage' => 'index');
    }

    public function resendOTP(Request $request)
    {
        $token = $request->route('token');

        // Get token from the database
        $getToken = VerificationOTP::where('token', $token)->first();
        if (!$getToken) {
            return response()->json(['success' => false, 'message' => 'Invalid token']);
        }

        $user = User::where('user_id', $getToken->user_id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid user']);
        }


        // Generate a random OTP for verification
        $otp = $otp = mt_rand(000000, 999999);
        // Generate a token for the user
        $newToken = Str::random(75);

        // // Save the OTP to the database
        VerificationOTP::updateOrInsert(
            ['user_id' => $user->user_id, 'token' => $token],
            [
                'user_id' => $user->user_id,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        //send otp to user
        $sms_content = "Your new Kind Giving OTP code is $otp, valid for 10 minutes.";
        $sms = new SMS($user->phone_number, $sms_content);
        $sms->singleSendSMS();

        return response()->json(['success' => true, 'message' => 'OTP Code Successfully Sent', 'newToken' => $newToken]);
    }

    public function verifyEmailIndex(Request $request)
    {
        $token = $request->route('token');
        // Get token from the database
        $getToken = VerificationOTP::where('token', $token)->first();
        if (!$getToken) {
            return redirect(route('login'))->with('error', 'Invalid token');
        }

        $user = User::where('user_id', $getToken->user_id)->first();

        if (!$user) {
            return redirect(route('login'))->with('error', 'Invalid user');
        }
        // Update user account
        $updateUser = User::where('user_id', $getToken->user_id)->update([
            'is_verified' => 'yes'
        ]);

        if (!$updateUser) {
            return redirect(route('login'))->with('error', 'Unable to update user account, try again');
        }

        if (auth()->user()->role == 'campaign_manager') {
            $role = 'manager';
        } else {
            $role = auth()->user()->role;
        }
        // Log in the user
        //   Auth::login($user);

        // Delete token
        $getToken->delete();
        return redirect(route($role . '.login'))->with('success', 'Account verified successfully');
    }
}
