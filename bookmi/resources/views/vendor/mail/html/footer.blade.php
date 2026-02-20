<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" style="padding: 24px 36px 32px;">
    <p style="margin:0 0 6px; color:#9ca3af; font-size:12px;">
        <strong style="color:#6b7280;">BookMi</strong> — La plateforme de réservation de talents en Côte d'Ivoire
    </p>
    <p style="margin:0 0 6px; color:#9ca3af; font-size:11px;">
        {{ Illuminate\Mail\Markdown::parse($slot) }}
    </p>
    <p style="margin:0; color:#d1d5db; font-size:11px;">
        &copy; {{ date('Y') }} BookMi. Tous droits réservés. &nbsp;|&nbsp;
        <a href="{{ config('app.url') }}" style="color:#9ca3af; text-decoration:underline;">bookmi.ci</a>
    </p>
</td>
</tr>
</table>
</td>
</tr>
