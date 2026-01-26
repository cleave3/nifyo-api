@extends('emails.layout')

@section('content')
    <div class="header">Password Reset Request</div>
    <div class="content">
        Hi {{ $name }},<br><br>
        We received a request to reset your password.<br>
        Click the button below to set a new password:
    </div>
    <a href="{{ $link }}" class="button">Reset Password</a>
    <div class="content" style="margin-top:24px;">
        If you did not request a password reset, please ignore this email or contact support.<br>
        <strong>Note:</strong> This link will expire in 10 minutes.
    </div>
@endsection