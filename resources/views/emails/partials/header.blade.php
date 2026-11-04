<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td align="center" style="padding:0 0 20px 0;">
            @if (! empty($logoUrl))
                {{-- 88px desktop mark; max-width keeps mobile balanced inside padded card --}}
                <img src="{{ $logoUrl }}" alt="{{ $siteName ?? 'Funnity' }}" width="88" height="88" style="display:block;width:88px;max-width:22vw;height:auto;border:0;outline:none;text-decoration:none;" />
            @else
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#7138F5;letter-spacing:-0.02em;">
                    {{ $siteName ?? 'Funnity' }}
                </div>
            @endif
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;">
            <div style="height:3px;width:56px;background-color:#7138F5;border-radius:3px;margin:0 auto;">&nbsp;</div>
        </td>
    </tr>
</table>
