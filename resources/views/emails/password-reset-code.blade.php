<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CFS admin password reset code</title>
</head>
<body style="margin:0;background:#f2f5f9;color:#10213f;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f5f9;padding:28px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:540px;background:#fff;border:1px solid #dce4ee;border-radius:14px;overflow:hidden;">
                <tr><td style="background:#07183a;padding:20px 24px;color:#fff;">
                    <div style="font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#f6a343;">CFS Admin</div>
                    <div style="font-size:21px;font-weight:700;margin-top:6px;">Reset your password</div>
                </td></tr>
                <tr><td style="padding:24px;">
                    <p style="font-size:15px;line-height:1.55;margin:0 0 8px;">Use this one-time code to continue resetting your CFS admin password.</p>
                    <p style="font-size:13px;line-height:1.5;color:#64748b;margin:0 0 20px;">Enter the six digits on the Verify Your Email page.</p>
                    <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto 20px;"><tr>
                        @foreach (str_split((string) $code) as $digit)
                            <td style="padding:0 3px;"><div style="width:42px;height:48px;line-height:48px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;color:#07183a;font-size:24px;font-weight:700;">{{ $digit }}</div></td>
                        @endforeach
                    </tr></table>
                    <div style="background:#fff7ed;border-left:4px solid #df740d;border-radius:5px;padding:12px 14px;color:#75400b;font-size:13px;line-height:1.5;">
                        This code expires in <strong>5 minutes</strong> and can only be used once. CFS staff will never ask you to share it.
                    </div>
                    <p style="color:#64748b;font-size:12px;line-height:1.5;margin:20px 0 0;">If you did not request this password reset, you can safely ignore this email. Your password will not change unless the code is verified and a new password is submitted.</p>
                </td></tr>
                <tr><td style="border-top:1px solid #e2e8f0;padding:14px 24px;color:#64748b;font-size:11px;">CFS · Your Feedback. A Better Experience.</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
