@extends('layouts.client')

@section('title', 'Réserver ' . $talent->stage_name . ' — BookMi')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Package card ── */
.pkg-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 18px;
    border-radius: 14px;
    border: 2px solid #E5E1DA;
    background: #FFFFFF;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    position: relative;
}
.pkg-option:hover {
    border-color: #FF6B35;
    background: #FFF8F5;
    box-shadow: 0 4px 16px rgba(255,107,53,0.10);
}
.pkg-option.selected {
    border-color: #FF6B35;
    background: #FFF8F5;
    box-shadow: 0 4px 20px rgba(255,107,53,0.14);
}

/* ── Form inputs ── */
.booking-input {
    width: 100%;
    padding: 13px 16px;
    border-radius: 12px;
    border: 1.5px solid #E5E1DA;
    background: #FDFCFA;
    font-family: 'Nunito', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1A2744;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.booking-input::placeholder { color: #B0A89E; font-weight: 500; }
.booking-input:focus {
    border-color: #FF6B35;
    box-shadow: 0 0 0 3px rgba(255,107,53,0.12);
    background: #FFFFFF;
}

/* ── CTA Button ── */
.btn-submit {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #FF6B35 0%, #f0520f 100%);
    color: #fff; font-weight: 800; font-size: 0.95rem; font-family: 'Nunito', sans-serif;
    border-radius: 14px; border: none; cursor: pointer;
    box-shadow: 0 4px 20px rgba(255,107,53,0.35);
    transition: transform 0.2s, box-shadow 0.2s;
    width: 100%;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(255,107,53,0.45); }

/* ── Label ── */
.form-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 800;
    color: #1A2744;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    margin-bottom: 8px;
}
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:860px;"
     x-data="{
        selectedPkg: {{ $talent->servicePackages->isNotEmpty() ? $talent->servicePackages->first()->id : 'null' }},
        selectedAmount: {{ $talent->servicePackages->isNotEmpty() ? $talent->servicePackages->first()->cachet_amount : ($talent->cachet_amount ?? 0) }},
        commissionRate: 15,
        get commission() { return Math.round(this.selectedAmount * this.commissionRate / 100); },
        get total() { return this.selectedAmount + this.commission; },
        selectPkg(id, amount) { this.selectedPkg = id; this.selectedAmount = amount; },
        fmt(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '\u202f'); }
     }">

    {{-- Flash --}}
    @if(session('error'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <a href="{{ url()->previous() }}" style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;font-weight:700;color:#8A8278;text-decoration:none;margin-bottom:14px;transition:color 0.2s;" onmouseover="this.style.color='#FF6B35'" onmouseout="this.style.color='#8A8278'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            Retour
        </a>
        <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Réserver un talent</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Envoyez votre demande de prestation à {{ $talent->stage_name }}</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

        {{-- ── Formulaire principal ── --}}
        <div>
            <form action="{{ route('client.bookings.store') }}" method="POST">
                @csrf
                <input type="hidden" name="talent_profile_id" value="{{ $talent->id }}">

                {{-- Talent card --}}
                <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:16px;">
                    {{-- Avatar --}}
                    @if($talent->cover_photo_url)
                        <img src="{{ $talent->cover_photo_url }}" alt="{{ $talent->stage_name }}"
                             style="width:56px;height:56px;border-radius:14px;object-fit:cover;flex-shrink:0;">
                    @else
                        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#1A2744,#2563EB);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.4rem;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($talent->stage_name, 0, 1)) }}
                        </div>
                    @endif
                    <div style="flex:1;min-width:0;">
                        <p style="font-weight:900;font-size:1.05rem;color:#1A2744;margin:0 0 3px 0;">{{ $talent->stage_name }}</p>
                        <p style="font-size:0.78rem;color:#8A8278;font-weight:500;margin:0;">{{ $talent->category->name ?? '' }}{{ $talent->city ? ' · ' . $talent->city : '' }}</p>
                    </div>
                    @if($talent->is_verified)
                    <span style="font-size:0.7rem;font-weight:800;padding:4px 10px;border-radius:9999px;background:#F0FDF4;border:1px solid #86EFAC;color:#15803D;flex-shrink:0;">✓ Vérifié</span>
                    @endif
                </div>

                {{-- Packages --}}
                @if($talent->servicePackages->isNotEmpty())
                <div class="dash-fade" style="animation-delay:120ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;margin-bottom:20px;">
                    <h2 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0 0 16px 0;">Choisir un package</h2>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($talent->servicePackages as $pkg)
                        <label class="pkg-option" :class="selectedPkg === {{ $pkg->id }} ? 'selected' : ''"
                               @click="selectPkg({{ $pkg->id }}, {{ $pkg->cachet_amount }})">
                            {{-- Radio --}}
                            <div style="width:20px;height:20px;border-radius:50%;border:2px solid;flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
                                 :style="selectedPkg === {{ $pkg->id }} ? 'border-color:#FF6B35;background:#FF6B35;' : 'border-color:#D1C9C1;background:#FDFCFA;'">
                                <div x-show="selectedPkg === {{ $pkg->id }}" style="width:8px;height:8px;border-radius:50%;background:#fff;"></div>
                            </div>
                            <input type="radio" name="service_package_id" value="{{ $pkg->id }}"
                                   {{ $loop->first ? 'checked' : '' }}
                                   x-model="selectedPkg"
                                   style="display:none;">
                            <div style="flex:1;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:3px;">
                                    <span style="font-weight:800;font-size:0.9rem;color:#1A2744;">{{ $pkg->name }}</span>
                                    <span style="font-weight:900;font-size:0.95rem;color:#FF6B35;flex-shrink:0;">{{ number_format($pkg->cachet_amount, 0, ',', ' ') }} <span style="font-size:0.72rem;font-weight:700;color:#B0A89E;">FCFA</span></span>
                                </div>
                                @if($pkg->description)
                                <p style="font-size:0.78rem;color:#8A8278;margin:0;line-height:1.5;font-weight:500;">{{ $pkg->description }}</p>
                                @endif
                                @if($pkg->duration_minutes)
                                <p style="font-size:0.72rem;color:#B0A89E;margin:4px 0 0;font-weight:600;display:flex;align-items:center;gap:4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $pkg->duration_minutes >= 60 ? floor($pkg->duration_minutes / 60).'h' : $pkg->duration_minutes.'min' }}
                                </p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Date & Lieu --}}
                <div class="dash-fade" style="animation-delay:160ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;margin-bottom:20px;">
                    <h2 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0 0 18px 0;">Détails de l'événement</h2>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label" for="event_date">Date de l'événement</label>
                            <input type="date" id="event_date" name="event_date"
                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                   value="{{ old('event_date') }}"
                                   class="booking-input" required>
                            @error('event_date')
                            <p style="color:#EF4444;font-size:0.75rem;font-weight:600;margin:6px 0 0;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" for="start_time">
                                Heure de début
                                <span style="font-weight:500;text-transform:none;letter-spacing:0;color:#B0A89E;">(optionnel)</span>
                            </label>
                            <input type="time" id="start_time" name="start_time"
                                   step="1800"
                                   value="{{ old('start_time') }}"
                                   class="booking-input">
                            @error('start_time')
                            <p style="color:#EF4444;font-size:0.75rem;font-weight:600;margin:6px 0 0;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label class="form-label" for="event_location">Lieu</label>
                        <input type="text" id="event_location" name="event_location"
                               placeholder="Abidjan, Cocody…"
                               value="{{ old('event_location') }}"
                               class="booking-input" required>
                        @error('event_location')
                        <p style="color:#EF4444;font-size:0.75rem;font-weight:600;margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="message">Message au talent <span style="color:#B0A89E;font-weight:500;text-transform:none;letter-spacing:0;">(optionnel)</span></label>
                        <textarea id="message" name="message" rows="4"
                                  placeholder="Décrivez votre événement, vos attentes, le thème…"
                                  class="booking-input" style="resize:vertical;min-height:100px;">{{ old('message') }}</textarea>
                        @error('message')
                        <p style="color:#EF4444;font-size:0.75rem;font-weight:600;margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Mobile: récapitulatif before submit --}}
                <div class="dash-fade" style="animation-delay:200ms;display:none;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:20px 24px;margin-bottom:20px;">
                    {{-- Mobile récap is handled by sidebar on desktop --}}
                </div>

                {{-- Submit --}}
                <div class="dash-fade" style="animation-delay:240ms;">
                    <button type="submit" class="btn-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Envoyer la demande
                    </button>
                    <p style="text-align:center;font-size:0.75rem;color:#B0A89E;font-weight:500;margin:12px 0 0;">
                        Le paiement n'est demandé qu'après l'acceptation du talent. Annulation gratuite 14j avant.
                    </p>
                </div>
            </form>
        </div>

        {{-- ── Sidebar récapitulatif ── --}}
        <div class="dash-fade" style="animation-delay:100ms;position:sticky;top:24px;">
            <div style="background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;">
                <h3 style="font-size:0.88rem;font-weight:900;color:#1A2744;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.04em;">Récapitulatif</h3>

                {{-- Cachet --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #EAE7E0;">
                    <span style="font-size:0.82rem;font-weight:600;color:#6B7280;">Cachet talent</span>
                    <span style="font-size:0.88rem;font-weight:800;color:#1A2744;" x-text="fmt(selectedAmount) + '\u202f' + 'FCFA'">{{ number_format($talent->servicePackages->isNotEmpty() ? $talent->servicePackages->first()->cachet_amount : ($talent->cachet_amount ?? 0), 0, ',', ' ') }} FCFA</span>
                </div>

                {{-- Commission --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #EAE7E0;">
                    <span style="font-size:0.82rem;font-weight:600;color:#6B7280;">Commission BookMi (15%)</span>
                    <span style="font-size:0.88rem;font-weight:800;color:#1A2744;" x-text="fmt(commission) + '\u202f' + 'FCFA'"></span>
                </div>

                {{-- Total --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0 0;">
                    <span style="font-size:0.9rem;font-weight:900;color:#1A2744;">Total</span>
                    <span style="font-size:1.1rem;font-weight:900;color:#FF6B35;" x-text="fmt(total) + '\u202f' + 'FCFA'"></span>
                </div>

                {{-- Info séquestre --}}
                <div style="margin-top:16px;padding:12px 14px;border-radius:12px;background:#F0FDF4;border:1px solid #86EFAC;">
                    <p style="font-size:0.75rem;color:#15803D;font-weight:700;margin:0;display:flex;align-items:flex-start;gap:6px;line-height:1.5;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#15803D" stroke-width="2" style="flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Votre paiement est bloqué en séquestre et libéré au talent uniquement après la prestation.
                    </p>
                </div>

                {{-- Garanties --}}
                <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:600;color:#6B7280;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="#15803D" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Aucun paiement avant acceptation
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:600;color:#6B7280;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="#15803D" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Réponse sous 24h garantie
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:600;color:#6B7280;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="#15803D" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Annulation gratuite (14j avant)
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
