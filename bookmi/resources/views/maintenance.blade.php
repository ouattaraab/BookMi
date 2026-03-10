<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — BookMi</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0D1B35;
            color: #E2E8F0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .stars {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(100, 181, 246, 0.04) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 107, 53, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
            max-width: 560px;
            width: 100%;
        }

        .logo-wrap {
            margin-bottom: 2rem;
        }

        .logo-img {
            width: 180px;
            filter: brightness(0) invert(1);
            opacity: 0.92;
        }

        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(100, 181, 246, 0.12);
            border: 1px solid rgba(100, 181, 246, 0.25);
            margin-bottom: 1.5rem;
        }

        .icon-wrap svg {
            width: 36px;
            height: 36px;
            color: #64B5F6;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #F1F5F9;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .message {
            font-size: 1rem;
            color: #94A3B8;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .countdown-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748B;
            margin-bottom: 0.5rem;
        }

        .countdown {
            font-size: 2.5rem;
            font-weight: 800;
            color: #64B5F6;
            letter-spacing: 0.05em;
            font-variant-numeric: tabular-nums;
            margin-bottom: 2rem;
        }

        .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(100, 181, 246, 0.4), transparent);
            margin: 2rem auto;
        }

        .tagline {
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #475569;
        }

        .pulse-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 1px solid rgba(100, 181, 246, 0.08);
            pointer-events: none;
            animation: pulse 3s ease-out infinite;
        }
        .pulse-ring:nth-child(2) { width: 500px; height: 500px; animation-delay: 1s; }
        .pulse-ring:nth-child(3) { width: 700px; height: 700px; animation-delay: 2s; }

        @keyframes pulse {
            0%   { opacity: 0.6; transform: translate(-50%, -50%) scale(0.95); }
            100% { opacity: 0;   transform: translate(-50%, -50%) scale(1.15); }
        }
    </style>
</head>
<body>
    <div class="stars"></div>

    {{-- Decorative pulse rings --}}
    <div class="pulse-ring"></div>
    <div class="pulse-ring"></div>
    <div class="pulse-ring"></div>

    <div class="container">

        <div class="logo-wrap">
            <img src="{{ asset('images/bookmi_logo.png') }}" alt="BookMi" class="logo-img"
                 onerror="this.style.display='none'; document.getElementById('logo-text').style.display='block'">
            <div id="logo-text" style="display:none; font-size:2rem; font-weight:800; color:#fff; letter-spacing:-0.03em;">
                Book<span style="color:#64B5F6;">Mi</span>
            </div>
        </div>

        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
            </svg>
        </div>

        <h1>Maintenance en cours</h1>
        <p class="message">{{ $message }}</p>

        @if($end_at)
            <div class="countdown-label">Nous revenons dans</div>
            <div class="countdown" id="countdown">--:--:--</div>
        @endif

        <div class="divider"></div>
        <p class="tagline">RÉSERVEZ LES MEILLEURS TALENTS</p>
    </div>

    @if($end_at)
    <script nonce="{{ app('csp_nonce') }}">
        (function () {
            const end = new Date('{{ $end_at }}');
            const el  = document.getElementById('countdown');

            function pad(n) { return String(Math.floor(n)).padStart(2, '0'); }

            function tick() {
                const diff = end - Date.now();
                if (diff <= 0) {
                    el.textContent = '00:00:00';
                    location.reload();
                    return;
                }
                const h = diff / 3600000;
                const m = (diff % 3600000) / 60000;
                const s = (diff % 60000) / 1000;
                el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            }

            tick();
            setInterval(tick, 1000);
        })();
    </script>
    @endif
</body>
</html>
