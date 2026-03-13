@extends('layouts.talent')

@section('title', 'Créer un Meet & Greet — BookMi Talent')

@section('content')
<div style="max-width:720px; margin:0 auto; padding:2rem 1.5rem;">

    <div style="margin-bottom:2rem;">
        <a href="{{ route('talent.meet-and-greet.index') }}"
           style="display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:0.85rem; font-weight:600; text-decoration:none; margin-bottom:1rem;">
            ← Retour à mes Meet & Greet
        </a>
        <h1 style="font-size:1.6rem; font-weight:900; color:#1a2744; margin:0 0 0.25rem;">Créer un Meet & Greet</h1>
        <p style="color:#6b7280; font-size:0.9rem; margin:0;">Proposez une expérience exclusive et intime à vos fans.</p>
    </div>

    @if($errors->any())
        <div style="background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:14px 18px; border-radius:12px; margin-bottom:1.5rem; font-size:0.875rem;">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('talent.meet-and-greet.store') }}" method="POST"
          style="background:white; border:1px solid #e5e1da; border-radius:20px; padding:2rem;">
        @csrf

        {{-- Titre --}}
        <div style="margin-bottom:1.25rem;">
            <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                Titre de l'événement *
            </label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   placeholder="Ex: Evening privé avec DJ Kass"
                   style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.95rem; font-weight:600; color:#1a2744; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                   onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        {{-- Description --}}
        <div style="margin-bottom:1.25rem;">
            <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                Description de l'expérience
            </label>
            <textarea name="description" rows="4"
                      placeholder="Décrivez ce que vivront vos fans : séance photo, discussion, performance privée..."
                      style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:500; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; resize:vertical; transition:border-color 0.2s;"
                      onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'">{{ old('description') }}</textarea>
        </div>

        {{-- Date + Heure --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
            <div>
                <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                    Date *
                </label>
                <input type="date" name="event_date" value="{{ old('event_date') }}" required
                       min="{{ now()->addDay()->format('Y-m-d') }}"
                       style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:600; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
            <div>
                <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                    Heure *
                </label>
                <input type="time" name="event_time" value="{{ old('event_time', '20:00') }}" required
                       style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:600; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
        </div>

        {{-- Lieu --}}
        <div style="margin-bottom:1.25rem;">
            <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                Lieu <span style="color:#9ca3af; font-weight:600; text-transform:none;">(révélé aux participants après inscription)</span>
            </label>
            <input type="text" name="venue_address" value="{{ old('venue_address') }}"
                   placeholder="Ex: Cocody, Abidjan — adresse exacte partagée plus tard"
                   style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:600; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                   onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        {{-- Prix total + Places --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:2rem;">
            <div>
                <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                    Montant total souhaité (FCFA) *
                </label>
                <input type="number" name="total_price" value="{{ old('total_price') }}" required
                       min="1000" step="500" placeholder="Ex: 500000"
                       id="total_price_input"
                       style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:600; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'"
                       oninput="updatePricePerSeat()">
                <p style="color:#9ca3af; font-size:0.75rem; font-weight:500; margin:4px 0 0;">Commission BookMi de 15% déduite.</p>
            </div>
            <div>
                <label style="display:block; font-size:0.82rem; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
                    Nombre de places *
                </label>
                <input type="number" name="max_seats" value="{{ old('max_seats') }}" required
                       min="1" max="500" placeholder="Ex: 10"
                       id="max_seats_input"
                       style="width:100%; padding:12px 16px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:0.9rem; font-weight:600; color:#374151; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#1AB3FF'" onblur="this.style.borderColor='#e5e7eb'"
                       oninput="updatePricePerSeat()">
            </div>
        </div>

        {{-- Récapitulatif prix/place --}}
        <div id="price-recap" style="display:none; background:rgba(26,179,255,0.06); border:1px solid rgba(26,179,255,0.25); border-radius:14px; padding:14px 18px; margin-bottom:2rem;">
            <p style="color:#0369a1; font-size:0.875rem; font-weight:700; margin:0;">
                💡 Prix par place : <strong id="price-per-seat-display">—</strong> FCFA
                &nbsp;·&nbsp; Votre part nette estimée : <strong id="talent-net-display">—</strong> FCFA
            </p>
        </div>

        {{-- Submit --}}
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <a href="{{ route('talent.meet-and-greet.index') }}"
               style="display:inline-flex; align-items:center; padding:12px 20px; border-radius:12px; background:#f3f4f6; color:#374151; font-weight:700; font-size:0.9rem; text-decoration:none; border:1px solid #e5e7eb;">
                Annuler
            </a>
            <button type="submit"
                    style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; border-radius:12px; background:linear-gradient(135deg,#1AB3FF,#0090E8); color:white; font-weight:800; font-size:0.9rem; border:none; cursor:pointer; font-family:inherit; box-shadow:0 4px 14px rgba(26,179,255,0.35);">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                Créer le Meet & Greet
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script nonce="{{ app('csp_nonce') }}">
function updatePricePerSeat() {
    var totalPrice = parseInt(document.getElementById('total_price_input').value) || 0;
    var maxSeats   = parseInt(document.getElementById('max_seats_input').value)   || 0;
    var recap      = document.getElementById('price-recap');

    if (totalPrice > 0 && maxSeats > 0) {
        var pricePerSeat = Math.round(totalPrice / maxSeats);
        var talentNet    = Math.round(totalPrice * 0.85);
        document.getElementById('price-per-seat-display').textContent = pricePerSeat.toLocaleString('fr-FR');
        document.getElementById('talent-net-display').textContent     = talentNet.toLocaleString('fr-FR');
        recap.style.display = 'block';
    } else {
        recap.style.display = 'none';
    }
}
</script>
@endpush
@endsection
