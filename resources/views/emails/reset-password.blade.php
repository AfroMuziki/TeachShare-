@extends('emails.layout')

@section('title', 'Reset your password')
@section('preheader', 'Use this link to choose a new TeachShare password.')

@section('body')
    <h1 style="margin:0 0 16px 0;font-size:22px;line-height:30px;font-weight:normal;color:#1F2937;">Reset your password</h1>
    <p style="margin:0 0 8px 0;">Hi {{ $user->name }},</p>
    <p style="margin:0;">We received a request to reset your TeachShare password. Choose a new one with the button below.</p>

    @include('emails._button', ['url' => $resetUrl, 'label' => 'Reset password'])

    <p style="margin:0;font-size:13px;color:#6B7280;">This link expires in 60 minutes. If the button doesn't work, copy this address into your browser:</p>
    <p style="margin:8px 0 0 0;font-size:12px;word-break:break-all;"><a href="{{ $resetUrl }}" style="color:#0E9AA7;">{{ $resetUrl }}</a></p>
@endsection

@section('footnote')
    Didn't ask for this? Your password hasn't changed, and you can ignore this email.
@endsection
