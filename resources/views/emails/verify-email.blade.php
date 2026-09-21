@extends('emails.layout')

@section('title', 'Verify your email')
@section('preheader', 'Confirm your email address to finish setting up TeachShare.')

@section('body')
    <h1 style="margin:0 0 16px 0;font-size:22px;line-height:30px;font-weight:normal;color:#1F2937;">Verify your email</h1>
    <p style="margin:0 0 8px 0;">Hi {{ $user->name }},</p>
    <p style="margin:0;">Thanks for joining TeachShare. Confirm your email address to activate your account.</p>

    @include('emails._button', ['url' => $verifyUrl, 'label' => 'Verify email'])

    <p style="margin:0;font-size:13px;color:#6B7280;">This link expires in 60 minutes. If the button doesn't work, copy this address into your browser:</p>
    <p style="margin:8px 0 0 0;font-size:12px;word-break:break-all;"><a href="{{ $verifyUrl }}" style="color:#0E9AA7;">{{ $verifyUrl }}</a></p>
@endsection

@section('footnote')
    Didn't create a TeachShare account? You can ignore this email.
@endsection
