<?php

namespace App\Http\Controllers;

use App\Mail\SupportTicket\NewTicket;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SupportDesk extends Controller
{
    public function index(Request $request)
    {
        $user_id = 3518691972;
        $user = User::where('user_id', $user_id)->first();
        $tickets = SupportTicket::where('user_id', $user->user_id)->latest()->paginate(10) ?: [];
        return view('support-desk.index', compact('tickets'));
    }

    public function create(Request $request)
    { // Validate the request
        $validator = Validator::make($request->all(), [
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:10240', // Adjust file types and size as needed
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:800',
            'category' => 'required|string',
            'priority' => 'required|string',
        ], [
            'attachment.max' => 'The file must be at most 10 megabytes',
            'description.max' => 'The description must be at most 255 characters',
            'description.required' => 'The description field is required',
            'subject.max' => 'The subject must be at most 255 characters',
            'subject.required' => 'The subject field is required',
            'category.required' => 'The category field is required',
            'priority.required' => 'The priority field is required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Handle validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        $ticket_id = Str::random(5);
        // Check if a file was provided before trying to store it
        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            // Upload attachment to CDN folder in public
            $file = $request->file('attachment');
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $filenametostore = Str::random(28) . time() . '.' . $extension;
            $destinationPath = public_path() . '/cdn/support-tickets';
            $attachmentPath = $file->move($destinationPath, $filenametostore);
            $attachmentUrl = asset('cdn/support-tickets/' . $filenametostore);
        }
        // Create the new support ticket with attachment path
        $newTicket = SupportTicket::create([
            'user_id' => $user->user_id,
            'ticket_id' => $ticket_id,
            'subject' => $request->subject,
            'message' => $request->description,
            'category' => $request->category,
            'status' => 'pending',
            'priority' => $request->priority,
            'chat' => '',
            'file_attachment' => $attachmentUrl,
        ]);


        $subject = 'New Support Ticket Created';
        Mail::to($user->email)->send(new NewTicket($subject, $ticket_id, $attachmentUrl));
        // return ((new NewTicket($subject, $ticket_id, $attachmentUrl)))->render(); 
        // Check if the new ticket was created
        if ($newTicket) {
            // Redirect the user to the ticket page
            return redirect()->back()->with('success', 'Ticket created successfully');
        }
    }
}
