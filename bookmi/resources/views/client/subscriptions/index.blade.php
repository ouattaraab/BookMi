@extends('layouts.client')

@section('title', 'Mes abonnements — BookMi')

@section('head')
<style>
main.page-content { background:#F2EFE9 !important; }
@keyframes fadeUp {
    from { opacity:0; transform:translateY(22px); }
    to   { opacity:1; transform:translateY(0); }
}
.dash-fade { opacity:0; animation:fadeUp 0.5s cubic-bezier(0.16,1,0.3,1) forwards; }
.sub-card {
    border-radius:18px; background:#fff; border:1px solid #E5E1DA;
    box-shadow:0 2px 12px rgba(26,39,68,0.06);
    transition:transform 0.25s cubic-bezier(0.16,1,0.3,1), box-shadow 0.25s, border-color 0.25s;
    overflow:hidden;
}
.sub-card:hover {
    transform:translateY(-5px); border-color:#1AB3FF;
    box-shadow:0 14px 40px rgba(26,179,255,0.12);
}
</style>
@endsection

@section('content')
<div style="max-width:1100px; margin:0 auto; padding:2rem 1.5rem;">

    {{-- Header --}}
    <div class="dash-fade" style="margin-bottom:2rem;">
        <h1 style="font-size:1.6rem; font-weight:900; color:#1a2744; margin:0 0 0.25rem;">Mes abonnements</h1>
        <p style="color:#6B7280; font-size:0.9rem; margin:0;">
            {{ $subscriptions->count() }} artiste{{ $subscriptions->count() !== 1 ? 's' : '' }} suivi{{ $subscriptions->count() !== 1 ? 's' : '' }}
        </p>
    </div>

    @if(session('success'))
    <div class="dash-fade" style="background:#D1FAE5; border:1px solid #A7F3D0; color:#065F46; padding:12px 16px; border-radius:10px; font-size:0.875rem; font-weight:700; margin-bottom:1.5rem;">
        {{ session('success') }}
    </div>
    @endif

    @if($subscriptions->isEmpty())
    <div class="dash-fade" style="text-align:center; padding:4rem 2rem;">
        <div style="width:64px; height:64px; background:#EDE9FE; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#7C3AED" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h3 style="font-size:1.1rem; font-weight:800; color:#1a2744; margin:0 0 0.5rem;">Aucun abonnement</h3>
        <p style="color:#6B7280; font-size:0.875rem; margin:0 0 1.5rem;">Suivez vos artistes préférés pour être notifié de leurs nouveautés.</p>
        <a href="/talents" style="display:inline-block; background:linear-gradient(135deg,#1AB3FF,#0090E8); color:white; font-weight:800; font-size:0.875rem; padding:10px 24px; border-radius:100px; text-decoration:none;">
            Découvrir les talents
        </a>
    </div>
    @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1.25rem;">
        @foreach($subscriptions as $i => $follow)
        @php
            $talent = $follow->talentProfile;
            $init   = strtoupper(substr($talent->stage_name ?? 'A', 0, 1));
            $delay  = $i * 0.06;
        @endphp
        <div class="sub-card dash-fade" style="animation-delay:{{ $delay }}s;">

            {{-- Top color bar --}}
            <div style="height:4px; background:linear-gradient(90deg,#1AB3FF,#7C3AED);"></div>

            <div style="padding:1.5rem;">
                {{-- Avatar + nom --}}
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:1.25rem;">
                    @if($talent->profile_photo)
                        <img src="{{ Storage::url($talent->profile_photo) }}" alt="{{ $talent->stage_name }}"
                             style="width:52px; height:52px; border-radius:14px; object-fit:cover; flex-shrink:0;">
                    @else
                        <div style="width:52px; height:52px; border-radius:14px; background:linear-gradient(135deg,#1AB3FF,#0090E8); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.25rem; color:white; flex-shrink:0;">
                            {{ $init }}
                        </div>
                    @endif
                    <div style="flex:1; min-width:0;">
                        <p style="font-weight:800; font-size:0.95rem; color:#1a2744; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $talent->stage_name }}
                        </p>
                        <p style="color:#6B7280; font-size:0.78rem; font-weight:600; margin:2px 0 0;">
                            {{ $talent->category->name ?? '—' }}
                            @if($talent->city) · {{ $talent->city }} @endif
                        </p>
                    </div>
                </div>

                {{-- Abonné depuis --}}
                <p style="color:#9CA3AF; font-size:0.75rem; font-weight:600; margin:0 0 1.25rem;">
                    Abonné depuis le {{ $follow->created_at->translatedFormat('d F Y') }}
                </p>

                {{-- Actions --}}
                <div style="display:flex; gap:8px;">
                    <a href="{{ route('talent.show', $talent->slug) }}"
                       style="flex:1; text-align:center; padding:9px 0; border-radius:10px; background:linear-gradient(135deg,#1AB3FF,#0090E8); color:white; font-weight:800; font-size:0.8rem; text-decoration:none;">
                        Voir le profil
                    </a>
                    <form action="{{ route('client.subscriptions.destroy', $talent->id) }}" method="POST" style="flex:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('Se désabonner de {{ $talent->stage_name }} ?')"
                                style="padding:9px 12px; border-radius:10px; background:#FEE2E2; border:1px solid #FECACA; color:#DC2626; font-weight:700; font-size:0.8rem; cursor:pointer; font-family:inherit; white-space:nowrap;">
                            Se désabonner
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
