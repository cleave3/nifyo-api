@extends('emails.layout')

@section('content')
    <div class="header">Welcome to the Team, {{ $first_name }}!</div>
    <div class="content">
        Your account has been created successfully.<br>
        Please find your login details below:
    </div>
    <table class="info-table">
        <tr>
            <td class="info-label">Username:</td>
            <td>{{ $email }} or {{ $phone }}</td>
        </tr>
        <tr>
            <td class="info-label">Password:</td>
            <td>{{ $password }}</td>
        </tr>
    </table>
    <a href="{{ $link }}" class="button">Log In</a>
    <div class="content" style="margin-top:24px;">
        Please keep this information secure.<br>
        If you did not request this account, please contact support immediately.
    </div>
@endsection