@extends('layouts.talent')

@section('title', 'Attestation de revenus — BookMi Talent')

@section('head')
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.cert-fade { opacity: 0; animation: fadeUp 0.52s cubic-bezier(0.16,1,0.3,1) forwards; }
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:640px;">

    {{-- Flash --}}
    @if(session('error'))
    <div class="cert-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="cert-fade" style="animation-delay:0ms;display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <a href="{{ route('talent.earnings') }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:12px;font-size:0.8rem;font-weight:700;background:#FFFFFF;border:1.5px solid #E5E1DA;color:#8A8278;text-decoration:none;box-shadow:0 1px 4px rgba(26,39,68,0.06);"
           onmouseover="this.style.borderColor='#FF6B35';this.style.color='#FF6B35'"
           onmouseout="this.style.borderColor='#E5E1DA';this.style.color='#8A8278'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            Retour
        </a>
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 4px;line-height:1.15;">
                Attestation de revenus
            </h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Téléchargez votre attestation fiscale annuelle</p>
        </div>
    </div>

    {{-- Card --}}
    <div class="cert-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">

        <div style="padding:20px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;flex-shrink:0;"></div>
            <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Générer l'attestation</h2>
        </div>

        <div style="padding:24px;">
            @if(empty($availableYears))
            <div style="padding:32px 0;text-align:center;">
                <div style="width:52px;height:52px;border-radius:14px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.18);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#FF6B35" stroke-width="1.75" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <p style="font-size:0.9rem;font-weight:700;color:#8A8278;margin:0 0 4px;">Aucune prestation terminée</p>
                <p style="font-size:0.78rem;color:#B0A89E;font-weight:500;margin:0;">Les attestations sont disponibles une fois vos premières prestations terminées.</p>
            </div>
            @else
            <p style="font-size:0.875rem;color:#6B7280;font-weight:500;margin:0 0 20px;line-height:1.6;">
                Sélectionnez l'année fiscale pour laquelle vous souhaitez télécharger votre attestation de revenus au format PDF.
            </p>
            <form action="{{ route('talent.revenue-certificate.download') }}" method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div style="flex:1;min-width:160px;">
                    <label style="display:block;font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#8A8278;margin-bottom:8px;">Année fiscale</label>
                    <select name="year"
                            style="width:100%;border:1.5px solid #E5E1DA;border-radius:12px;padding:12px 16px;font-size:0.9rem;font-family:'Nunito',sans-serif;font-weight:700;color:#1A2744;background:#FFFFFF;outline:none;transition:border-color 0.2s;"
                            onfocus="this.style.borderColor='#FF6B35'"
                            onblur="this.style.borderColor='#E5E1DA'">
                        @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        style="padding:12px 28px;border-radius:14px;font-size:0.875rem;font-weight:800;color:white;background:linear-gradient(135deg,#FF6B35,#C85A20);border:none;cursor:pointer;font-family:'Nunito',sans-serif;box-shadow:0 4px 14px rgba(255,107,53,0.28);transition:transform 0.2s;display:flex;align-items:center;gap:8px;white-space:nowrap;"
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform=''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Télécharger le PDF
                </button>
            </form>
            @endif
        </div>

    </div>

    {{-- Info --}}
    <div class="cert-fade" style="animation-delay:150ms;margin-top:16px;padding:14px 18px;border-radius:12px;background:#EFF6FF;border:1px solid #BFDBFE;display:flex;gap:10px;align-items:flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="font-size:0.8rem;color:#1E40AF;font-weight:500;margin:0;line-height:1.5;">
            L'attestation récapitule vos prestations terminées sur l'année sélectionnée, avec le détail mensuel de vos revenus nets et la commission BookMi déduite.
        </p>
    </div>

</div>
@endsection
