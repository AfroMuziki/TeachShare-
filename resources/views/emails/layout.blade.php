<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background-color:#F3F6FB;">
    {{-- Hidden preheader shown in inbox previews --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">@yield('preheader')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F3F6FB;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding:0 0 20px 0;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:bold;color:#2F5BFF;">
                            TeachShare
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;border-radius:16px;padding:36px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#374151;">
                            @yield('body')
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 8px 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#9CA3AF;">
                            @yield('footnote')
                            <br>&copy; {{ date('Y') }} TeachShare
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
