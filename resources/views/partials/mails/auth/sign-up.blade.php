@extends('partials.mails.header-footer')
@section('content') 
        <h1>{{ $subject }}</h1>
        <p>Dear {{ $user->name }},</p>
        <p>Thank you for creating an account with KindGiving. Please use the following OTP to verify your account:</p>
        <p><strong>{{ $otp }}</strong></p>
        <p>This OTP is valid for 10 minutes. If you did not request this verification, please ignore this email.</p>
        <p>Regards,<br>KindGiving Team</p>
 
@endsection
