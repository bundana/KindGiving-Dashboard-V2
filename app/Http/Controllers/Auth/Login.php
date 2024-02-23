<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class Login extends Controller
{
    public function show(Request $request)
    {
        return view('pages.auth.login');
    }

    public function processLogin(Request $request)
    {
        //        sleep(50);
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email_phone' => ['required'],
            'password' => ['required'],
        ], [
            'email_phone.required' => 'The phone number or email field is required.',
            'password.required' => 'The password field is required.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage]);
        }

        // Check if the input is a valid email
        $isEmail = filter_var($request->input('email_phone'), FILTER_VALIDATE_EMAIL);

        // Find the user by email or phone number
        $user = $isEmail
            ? User::where('email', $request->input('email_phone'))->first()
            : User::where('phone_number', $request->input('email_phone'))->first();

        // Check if the user exists
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User does not exist with ' . ($isEmail ? 'email' : 'phone number')]);
        }

        // Attempt to authenticate the user with "Remember Me" functionality
        if (Auth::attempt([$isEmail ? 'email' : 'phone_number' => $request->input('email_phone'), 'password' => $request->input('password'), 'is_verified' => 'yes'], $request->has('remember'))) {
            $request->session()->regenerate();

            // Log in the user
//            Auth::login($user, $request->has('remember'));
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
            return response()->json(['success' => true, 'message' => 'Login Successfully', 'redirectedPage' => $redirectedPage]);
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        return redirect(route('login'))->with('success', 'You have been logged out!');
    }
}
