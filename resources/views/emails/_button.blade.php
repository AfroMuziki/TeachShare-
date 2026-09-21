{{-- Bulletproof button: table cell carries the background so Outlook keeps it. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px auto;">
    <tr>
        <td align="center" bgcolor="#2F5BFF" style="border-radius:999px;background-color:#2F5BFF;">
            <a href="{{ $url }}" target="_blank"
               style="display:inline-block;padding:14px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:999px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
