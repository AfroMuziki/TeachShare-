@extends('emails.layout')

@section('title', 'Welcome to TeachShare')
@section('preheader', 'Your email is verified. Your TeachShare account is ready.')

@section('body')
    <h1 style="margin:0 0 16px 0;font-size:22px;line-height:30px;font-weight:normal;color:#1F2937;">Welcome to TeachShare</h1>
    <p style="margin:0 0 8px 0;">Hi {{ $user->name }},</p>
    <p style="margin:0;">Your email is verified and your account is ready.</p>

    @include('emails._button', ['url' => $dashboardUrl, 'label' => 'Go to your dashboard'])
@endsection

@section('footnote')
    You're receiving this because you created a TeachShare account.
@endsection
