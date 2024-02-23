<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Exports\DonationsExport;
use App\Models\User;
use App\Http\Controllers\Controller;
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
use App\Mail\System\Users\CreateOrEdit;
use App\Models\ExchangeRate;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SystemManagement extends Controller
{
    public function systemUsers(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status') ?: ''; // Using the null coalescing operator

        $users = User::when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('user_id', "$keyword")
                    ->orWhere('phone_number', "$keyword")
                    ->orWhere('status', 'like', "%$keyword%");
            })
            ->latest()
            ->paginate(10);
        // Pass the list of users to the 'system.users' view
        return view('admin.system.users.index', compact('users', 'status', 'keyword'));
    }

    public function viewUser(Request $request)
    {
        $user = User::where('user_id', $request->id)->first();
        if (!$user) {
            return redirect(route('admin.system-users'))->with('error', 'User not found');
        }
        return view('admin.system.users.view', compact('user'));
    }

    public function addUser(Request $request)
    {

        // Validation rules
        $validator = Validator::make($request->all(), [
            'email_address' => ['required', 'email', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'digits:10', Rule::unique('users', 'phone_number')],
            'password' => ['nullable', 'confirmed'],
            'name' => ['required', 'string', 'min:5'],
            'status' => ['required'],
            'role' => ['required'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                File::image()->min('1kb')->max('10000kb'), // Increased max size to 10MB
            ],
        ]);

        // Validation custom error messages
        $messages = [
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a numeric value.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'name.required' => 'The name field is required.',
            'image.file' => 'The uploaded file must be a valid file.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must be at most 10 megabytes.',
            'status.required' => 'Please select user status',
            'role.required' => 'Please select role for the user'
        ];

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate a unique user_id with 10 numeric digits
        $user_id = mt_rand(1000000000, 9999999999);
        $phone_number = $request->input('phone_number') ?? null;
        $name = $request->input('name') ?? null;
        // Generate the Gravatar URL
        $gravatarUrl = Avatar::create($request->email_address)->toBase64();
        $gravatarUrl = Avatar::create($request->email_address)
            ->toGravatar(['d' => 'identicon', 'r' => 'pg', 's' => 100]);


        // valide password only if provided or generate one
        $password = "";
        if ($request->filled('password')) {
            // Validation rules
            $rules = [
                'password' => ['required', 'confirmed'],
            ];
            // Validation custom error messages
            $messages = [
                'password.required' => 'The password field is required.',
                'password.confirmed' => 'The password confirmation does not match.',
            ];

            // Run the validation
            $credentials = Validator::make($request->all(), $rules, $messages);

            if ($credentials->fails()) {
                $errorMessage = $credentials->errors()->first();
                redirect()->back()->with(['success', $errorMessage]);
            }
            $password = $request->input('password');
        } else {
            $password = Str::random(8);
        }

        // Get File Extension
        $imagefullPathUrl = "";
        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();
            $subfolder = 'users/'; // Generate Filename with Subfolder
            $filenametostore = $subfolder . Str::uuid() . Str::uuid() . time() . '.' . $extension;
            // Upload File to External Server (FTP)
            Helpers::uploadImageToFTP($filenametostore, $request->file('image'));

            // Get Full Path URL
            $basePath = "https://asset.kindgiving.org/cdn/"; // Replace with your actual base URL
            $imagefullPathUrl = $basePath . $filenametostore;
        } else {
            $imagefullPathUrl = $gravatarUrl; // Fix the key here ('avatar' instead of 'avatart')
        }

        // Save the user to the database
        $user = User::create([
            'user_id' => $user_id,
            'name' => $name,
            'phone_number' => $phone_number,
            'email' => $request->email_address,
            'role' => $request->role,
            'password' => Hash::make($password),
            'is_verified' => 'yes',
            'avatar' => $imagefullPathUrl,
            'status' => $request->status,
        ]);

        $subject = "Account Created Successfully";
        // Mail::to($request->email_address)->send(new CreateOrEdit($subject, $user, 'create'));

        // return (new CreateOrEdit($subject, $user, 'create'))->render();

        return redirect()->back()->with('success', 'User added successfully, password ' . $password);
    }

    public function editUser(Request $request)
    {
        // Retrieve the user ID from the request 
        $user_id = $request->id;
        // Retrieve campaign users associated with the campaign
        $user = User::where('user_id', $user_id)
            ->first();

        // Check if the user exists; if not, redirect back with an error message
        if (!$user) {
            return back()->with('error', 'user not found');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:5'],
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
            'password' => ['nullable', 'confirmed'],
            'status' => ['required'],
            'role' => ['required'],

        ], [
            'name.required' => 'The user name is required.',
            'email_address.required' => 'The email address field is required.',
            'email_address.email' => 'The email address must be a valid email address.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.numeric' => 'The phone number must be a valid number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'email_address.unique' => 'The email address has already been taken.',
            'phone_number.unique' => 'The phone number has already been taken.',
            'image.file' => 'The uploaded file must be a valid file.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must be at most 10 megabytes.',
            'status.required' => 'Please select user status',
            'role.required' => 'Please select role for the user'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update user information
        $user->name = $request->input('name');
        $user->email = $request->input('email_address');
        $user->phone_number = $request->input('phone_number');

        // Update password only if provided
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Get File Extension
        $imagefullPathUrl = "";
        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();
            $subfolder = 'users/'; // Generate Filename with Subfolder
            $filenametostore = $subfolder . Str::uuid() . Str::uuid() . time() . '.' . $extension;
            // Upload File to External Server (FTP)
            Helpers::uploadImageToFTP($filenametostore, $request->file('image'));

            // Get Full Path URL
            $basePath = "https://asset.kindgiving.org/cdn/"; // Replace with your actual base URL
            $imagefullPathUrl = $basePath . $filenametostore;
        } else {
            $imagefullPathUrl = $user->avatar; // Fix the key here ('avatar' instead of 'avatart')
        }
        $user->update([
            'avatar' => $imagefullPathUrl, // Fix the key here ('avatar' instead of 'avatart')
        ]);
        $user->save();
        $subject = "Account Information updated";
        // Mail::to($request->email_address)->send(new CreateOrEdit($subject, $user, 'update'));

        // return (new CreateOrEdit($subject, $user, 'update'))->render();

        // Redirect to a success route
        return redirect(route('admin.view-system-user', [$user->user_id]))->with('success', 'User updated successfully');
    }

    public function deleteUser(Request $request)
    {
        // Retrieve the user ID from the request 
        $user_id = $request->id;

        // Retrieve campaign users associated with the campaign
        $user = User::where('user_id', $user_id)
            ->first();

        // Check if the user exists; if not, redirect back with an error message
        if (!$user) {
            return back()->with('error', 'user not found');
        }

        // Delete user
        // $user->delete(); 

        $subject = "Kind Giving Account deleted";
        // Mail::to($request->email_address)->send(new CreateOrEdit($subject, $user, 'delete'));

        //    return (new CreateOrEdit($subject, $user, 'delete'))->render();

        // Redirect to a success route
        return redirect(route('admin.system-users'))->with('success', 'User deleted successfully');
    }

    public function exchangeRate(Request $request)
    {
        $exchangeRate = ExchangeRate::where('currency', 'usd')->first();
        return view('admin.system.exchange-rate', compact('exchangeRate'));
    }

    public function editExchangeRate(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'rate' => ['required', 'numeric'],
        ]);

        // Validation custom error messages
        $messages = [
            'rate.required' => 'The exchange rate field is required.',
            'rate.numeric' => 'The exchange rate must be a numeric value.',
        ];

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $rate = $exchangeRate = ExchangeRate::where('currency', 'usd')->first();
        ;
        if ($rate == $request->input('rate')) {
            return redirect()->back()->with('error', 'Exchange rate already set to ' . $request->input('rate'));
        }
        if (!$rate) {
            // create 
            ExchangeRate::create(
                [
                    'currency' => 'usd',
                    'rate' => $request->input('rate') ? : 12
                ]
            );
            // Redirect to a success route
            return redirect(route('admin.exchange-rate'))->with('success', 'Exchange rate created successfully');

        }
        // Update the exchange rate
        $exchangeRate = ExchangeRate::where('currency', 'usd')->first();
        $exchangeRate->rate = $request->input('rate');
        $exchangeRate->save();

        // Redirect to a success route
        return redirect(route('admin.exchange-rate'))->with('success', 'Exchange rate updated successfully');
    }
}
