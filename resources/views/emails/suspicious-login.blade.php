@extends('emails.layout')

@section('title', 'Suspicious sign-in activity')
@section('preheader', 'An IP address reached the hourly failed sign-in limit.')

@section('body')
    <h1 style="margin:0 0 16px 0;font-size:22px;line-height:30px;font-weight:normal;color:#1F2937;">Suspicious sign-in activity</h1>
    <p style="margin:0 0 16px 0;">One IP address reached the failed sign-in limit within an hour and is now blocked from signing in.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">
        <tr>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;color:#6B7280;width:40%;">IP address</td>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;">{{ $ip }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;color:#6B7280;">Failed attempts</td>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;">{{ $attempts }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;border-bottom:1px solid #E5E7EB;color:#6B7280;">Latest email tried</td>
            <td style="padding:8px 0;border-top:1px solid #E5E7EB;border-bottom:1px solid #E5E7EB;">{{ $email }}</td>
        </tr>
    </table>

    <p style="margin:16px 0 0 0;font-size:13px;color:#6B7280;">Full details are in the <code>login_attempts</code> table and the application log.</p>
@endsection

@section('footnote')
    Automated security alert from TeachShare.
@endsection
