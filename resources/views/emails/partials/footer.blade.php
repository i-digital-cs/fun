<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;border-top:1px solid #EEF0F7;">
    <tr>
        <td style="padding:18px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.55;color:#64748B;{{ ($dir ?? 'ltr') === 'rtl' ? 'text-align:right;direction:rtl;' : 'text-align:center;direction:ltr;' }}">
            <strong style="color:#475569;">{{ $siteName ?? 'Funnity' }}</strong><br />
            {{ __('auth.mail_footer_tagline') }}
        </td>
    </tr>
</table>
