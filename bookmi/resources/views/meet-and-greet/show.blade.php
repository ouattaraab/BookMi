@extends('layouts.public')

@section('title', $experience->title . ' — Meet & Greet BookMi')
@section('meta_description', Str::limit($experience->description ?? 'Expérience exclusive avec ' . ($experience->talentProfile->stage_name ?? ''), 160))

@section('content')
<div style="background:radial-gradient(ellipse 90% 70% at 15% 20%, rgba(26,179,255,0.18) 0%, transparent 55%), radial-gradient(ellipse 80% 60% at 85% 80%, rgba(139,92,246,0.16) 0%, transparent 55%), linear-gradient(135deg,#EFF8FF 0%,#F0EEFF 60%,#EFF8FF 100%); min-height:100vh; padding-bottom:4rem;">

    {{-- Hero --}}
    <div style="background:radial-gradient(ellipse 110% 65% at 10% 50%, rgba(26,179,255,0.14) 0%, transparent 55%), radial-gradient(ellipse 90% 55% at 90% 50%, rgba(139,92,246,0.12) 0%, transparent 55%), linear-gradient(135deg,rgba(239,248,255,0.95) 0%,rgba(240,238,255,0.95) 100%); padding:3rem 1.5rem 0; border-bottom:1px solid rgba(139,92,246,0.12);">
        <div style="max-width:860px; margin:0 auto;">

            {{-- Breadcrumb --}}
            <div style="margin-bottom:1.5rem;">
                <a href="{{ route('home') }}" style="color:#9CA3AF; font-size:0.8rem; font-weight:600; text-decoration:none;">Accueil</a>
                <span style="color:#D1D5DB; margin:0 6px;">›</span>
                <a href="{{ route('talent.show', $experience->talentProfile->slug) }}" style="color:#9CA3AF; font-size:0.8rem; font-weight:600; text-decoration:none;">{{ $experience->talentProfile->stage_name }}</a>
                <span style="color:#D1D5DB; margin:0 6px;">›</span>
                <span style="color:#6B7280; font-size:0.8rem; font-weight:600;">Meet & Greet</span>
            </div>

            {{-- Statut badge --}}
            @php
                $isFull      = $experience->status->value === 'full';
                $isCancelled = $experience->status->value === 'cancelled';
            @endphp
            @if($isFull)
                <span style="display:inline-flex; align-items:center; gap:6px; background:#FEF3C7; border:1px solid #FDE68A; color:#D97706; font-size:0.72rem; font-weight:800; padding:5px 14px; border-radius:100px; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1rem;">
                    🔒 Complet
                </span>
            @elseif($isCancelled)
                <span style="display:inline-flex; align-items:center; gap:6px; background:#FEE2E2; border:1px solid #FECACA; color:#DC2626; font-size:0.72rem; font-weight:800; padding:5px 14px; border-radius:100px; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1rem;">
                    Annulé
                </span>
            @else
                <span style="display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,rgba(26,179,255,0.10),rgba(139,92,246,0.10)); border:1px solid rgba(139,92,246,0.28); color:#7C3AED; font-size:0.72rem; font-weight:800; padding:5px 14px; border-radius:100px; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1rem;">
                    <span style="width:6px; height:6px; border-radius:50%; background:#1AB3FF; display:inline-block; animation:pulse 2s ease-in-out infinite;"></span>
                    Places disponibles
                </span>
            @endif

            <h1 style="font-size:clamp(1.8rem,4vw,2.6rem); font-weight:900; color:#1E1B4B; margin:0 0 1rem; letter-spacing:-0.02em;">
                {{ $experience->title }}
            </h1>

            {{-- Artiste --}}
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:2rem;">
                <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#1AB3FF,#8B5CF6); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.1rem; color:white;">
                    {{ strtoupper(substr($experience->talentProfile->stage_name, 0, 1)) }}
                </div>
                <div>
                    <a href="{{ route('talent.show', $experience->talentProfile->slug) }}"
                       style="color:#1E1B4B; font-weight:800; font-size:0.95rem; text-decoration:none;">
                        {{ $experience->talentProfile->stage_name }}
                    </a>
                    <p style="color:#9CA3AF; font-size:0.78rem; font-weight:600; margin:0;">
                        {{ $experience->talentProfile->category->name ?? '' }}
                        @if($experience->talentProfile->city) · {{ $experience->talentProfile->city }} @endif
                    </p>
                </div>
            </div>

            {{-- Pills info --}}
            <div style="display:flex; flex-wrap:wrap; gap:10px; padding-bottom:2rem;">
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span style="color:#1E1B4B; font-size:0.875rem; font-weight:700;">{{ $experience->event_date->isoFormat('dddd D MMMM YYYY') }}</span>
                </div>
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="#8B5CF6" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span style="color:#1E1B4B; font-size:0.875rem; font-weight:700;">{{ $experience->event_date->format('H\hi') }}</span>
                </div>
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span style="font-weight:800; font-size:0.875rem; background:linear-gradient(90deg,#1AB3FF,#8B5CF6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">{{ number_format($experience->price_per_seat, 0, ',', '.') }} FCFA <span style="font-weight:600;">/ pers.</span></span>
                </div>
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="{{ $isFull ? '#D97706' : '#059669' }}" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span style="color:{{ $isFull ? '#D97706' : '#059669' }}; font-size:0.875rem; font-weight:700;">
                        {{ $experience->seats_available }} place(s) disponible(s) sur {{ $experience->max_seats }}
                    </span>
                </div>
                @if($experience->venue_address)
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="#9CA3AF" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span style="color:#6B7280; font-size:0.875rem; font-weight:600;">
                        @if($experience->venue_revealed || ($myBooking && $myBooking->status->value !== 'cancelled'))
                            {{ $experience->venue_address }}
                        @else
                            Lieu révélé après inscription
                        @endif
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Corps --}}
    <div style="max-width:860px; margin:0 auto; padding:2rem 1.5rem 0;">
        <div style="display:grid; grid-template-columns:1fr 360px; gap:2rem; align-items:start;">

            {{-- Colonne gauche : description --}}
            <div>
                @if($experience->description)
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(12px); border:1px solid rgba(139,92,246,0.15); border-radius:16px; padding:1.75rem; margin-bottom:1.5rem; box-shadow:0 2px 16px rgba(26,179,255,0.07);">
                    <h2 style="font-size:1rem; font-weight:800; color:#1E1B4B; margin:0 0 1rem; display:flex; align-items:center; gap:8px;">
                        <span style="display:block; width:3px; height:16px; border-radius:2px; background:linear-gradient(180deg,#1AB3FF,#8B5CF6);"></span>
                        Au programme
                    </h2>
                    <p style="color:#6B7280; font-size:0.9rem; line-height:1.8; margin:0; white-space:pre-line;">{{ $experience->description }}</p>
                </div>
                @endif

                {{-- Options premium --}}
                @if($experience->premium_options && count($experience->premium_options) > 0)
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(12px); border:1px solid rgba(139,92,246,0.15); border-radius:16px; padding:1.75rem; box-shadow:0 2px 16px rgba(26,179,255,0.07);">
                    <h2 style="font-size:1rem; font-weight:800; color:#1E1B4B; margin:0 0 1rem; display:flex; align-items:center; gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Options Premium
                    </h2>
                    <div style="display:flex; flex-direction:column; gap:0.75rem;">
                        @foreach($experience->premium_options as $opt)
                        <div style="background:linear-gradient(135deg,rgba(26,179,255,0.06),rgba(139,92,246,0.06)); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:12px 16px; display:flex; align-items:center; justify-content:space-between;">
                            <div>
                                <p style="font-weight:700; color:#1E1B4B; font-size:0.88rem; margin:0;">{{ $opt['name'] ?? '' }}</p>
                                @if(!empty($opt['description']))
                                    <p style="color:#6B7280; font-size:0.78rem; margin:2px 0 0;">{{ $opt['description'] }}</p>
                                @endif
                            </div>
                            @if(!empty($opt['price']))
                                <span style="font-weight:800; font-size:0.9rem; white-space:nowrap; background:linear-gradient(90deg,#1AB3FF,#8B5CF6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                                    +{{ number_format((int)$opt['price'], 0, ',', '.') }} FCFA
                                </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Colonne droite : inscription --}}
            <div style="position:sticky; top:1.5rem;">
                <div style="background:rgba(255,255,255,0.82); backdrop-filter:blur(12px); border:1px solid rgba(139,92,246,0.20); border-radius:20px; padding:1.75rem; box-shadow:0 4px 30px rgba(26,179,255,0.12),0 2px 12px rgba(139,92,246,0.08);">

                    @if($isCancelled)
                        <div style="text-align:center; padding:1rem 0;">
                            <p style="color:#DC2626; font-weight:700; font-size:0.9rem;">Cet événement a été annulé.</p>
                            @if($experience->cancelled_reason)
                                <p style="color:#9CA3AF; font-size:0.8rem; margin-top:0.5rem;">{{ $experience->cancelled_reason }}</p>
                            @endif
                        </div>

                    @elseif($myBooking && $myBooking->status->value !== 'cancelled')
                        {{-- Déjà inscrit --}}
                        <div style="text-align:center; margin-bottom:1.25rem;">
                            <div style="width:52px; height:52px; background:#D1FAE5; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#059669" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <h3 style="color:#1E1B4B; font-weight:900; font-size:1rem; margin:0 0 0.25rem;">Vous êtes inscrit !</h3>
                            <p style="color:#6B7280; font-size:0.8rem; margin:0;">
                                {{ $myBooking->seats_count }} place(s) · {{ number_format($myBooking->total_amount, 0, ',', '.') }} FCFA
                            </p>
                            @if($myBooking->status->value === 'pending')
                                <p style="color:#D97706; font-size:0.78rem; font-weight:700; margin:8px 0 0;">En attente de confirmation</p>
                            @elseif($myBooking->status->value === 'confirmed')
                                <p style="color:#059669; font-size:0.78rem; font-weight:700; margin:8px 0 0;">Inscription confirmée ✓</p>
                            @endif
                        </div>
                        @auth
                        <form action="{{ route('client.meet-and-greet.cancel', $myBooking->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Annuler votre inscription ?')"
                                    style="width:100%; padding:11px; border-radius:12px; background:#FEE2E2; color:#DC2626; font-weight:700; font-size:0.85rem; border:1px solid #FECACA; cursor:pointer; font-family:inherit; transition:all 0.2s;">
                                Annuler mon inscription
                            </button>
                        </form>
                        @endauth

                    @elseif($isFull)
                        <div style="text-align:center; padding:1rem 0;">
                            <p style="color:#D97706; font-weight:800; font-size:1rem; margin:0 0 0.5rem;">🔒 Événement complet</p>
                            <p style="color:#9CA3AF; font-size:0.82rem;">Toutes les places ont été réservées.</p>
                        </div>

                    @else
                        {{-- Formulaire inscription --}}
                        <h3 style="color:#1E1B4B; font-weight:900; font-size:1rem; margin:0 0 0.25rem;">Réserver ma place</h3>
                        <p style="color:#9CA3AF; font-size:0.8rem; font-weight:600; margin:0 0 1.5rem;">
                            {{ $experience->seats_available }} place(s) disponible(s)
                        </p>

                        @if(session('success'))
                            <div style="background:#D1FAE5; border:1px solid #A7F3D0; color:#065F46; padding:12px; border-radius:10px; font-size:0.85rem; font-weight:700; margin-bottom:1rem;">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if(session('error'))
                            <div style="background:#FEE2E2; border:1px solid #FECACA; color:#DC2626; padding:12px; border-radius:10px; font-size:0.85rem; font-weight:700; margin-bottom:1rem;">
                                {{ session('error') }}
                            </div>
                        @endif

                        @auth
                            @if(auth()->user()->hasRole('client'))
                                <form action="{{ route('client.meet-and-greet.book') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="experience_id" value="{{ $experience->id }}">

                                    <div style="margin-bottom:1.25rem;">
                                        <label style="display:block; font-size:0.78rem; font-weight:800; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">
                                            Nombre de places
                                        </label>
                                        <select name="seats_count"
                                                style="width:100%; padding:12px 16px; border:1.5px solid rgba(139,92,246,0.25); border-radius:12px; background:rgba(255,255,255,0.9); color:#1E1B4B; font-size:0.9rem; font-weight:700; font-family:inherit; outline:none;">
                                            @for($i = 1; $i <= min(5, $experience->seats_available); $i++)
                                                <option value="{{ $i }}">{{ $i }} place{{ $i > 1 ? 's' : '' }} — {{ number_format($i * $experience->price_per_seat, 0, ',', '.') }} FCFA</option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div style="background:linear-gradient(135deg,rgba(26,179,255,0.07),rgba(139,92,246,0.07)); border:1px solid rgba(139,92,246,0.18); border-radius:12px; padding:12px 16px; margin-bottom:1.25rem;">
                                        <p style="color:#9CA3AF; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 4px;">Prix par place</p>
                                        <p style="font-size:1.5rem; font-weight:900; margin:0; line-height:1.1; background:linear-gradient(90deg,#1AB3FF,#8B5CF6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                                            {{ number_format($experience->price_per_seat, 0, ',', '.') }}
                                            <span style="font-size:0.85rem; font-weight:700;">FCFA</span>
                                        </p>
                                    </div>

                                    <button type="submit"
                                            style="width:100%; padding:14px; border-radius:14px; background:linear-gradient(135deg,#1AB3FF,#8B5CF6); color:white; font-weight:900; font-size:1rem; border:none; cursor:pointer; font-family:inherit; box-shadow:0 6px 20px rgba(26,179,255,0.35); transition:transform 0.15s, box-shadow 0.15s;"
                                            onmouseover="this.style.transform='scale(1.02)';this.style.boxShadow='0 10px 30px rgba(26,179,255,0.45),0 4px 16px rgba(139,92,246,0.3)'"
                                            onmouseout="this.style.transform='';this.style.boxShadow='0 6px 20px rgba(26,179,255,0.35)'">
                                        🎟 Réserver ma place
                                    </button>
                                </form>
                            @else
                                <p style="color:#9CA3AF; font-size:0.85rem; text-align:center; margin:0;">Seuls les clients peuvent réserver.</p>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               style="display:block; text-align:center; padding:14px; border-radius:14px; background:linear-gradient(135deg,#1AB3FF,#8B5CF6); color:white; font-weight:900; font-size:1rem; text-decoration:none; box-shadow:0 6px 20px rgba(26,179,255,0.35);">
                                Connexion pour réserver
                            </a>
                            <a href="{{ route('register') }}"
                               style="display:block; text-align:center; margin-top:10px; padding:12px; border-radius:12px; background:rgba(255,255,255,0.7); border:1.5px solid rgba(139,92,246,0.25); color:#7C3AED; font-weight:700; font-size:0.875rem; text-decoration:none;">
                                Créer un compte gratuitement
                            </a>
                        @endauth
                    @endif

                    {{-- Garanties --}}
                    <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid rgba(139,92,246,0.12); display:flex; flex-direction:column; gap:8px;">
                        @foreach(['Paiement sécurisé Mobile Money','Support BookMi 7j/7','Remboursement en cas d\'annulation'] as $g)
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            <span style="color:#6B7280; font-size:0.78rem; font-weight:600;">{{ $g }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
