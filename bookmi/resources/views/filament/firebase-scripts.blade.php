{{--
    BookMi Admin — Firebase Cloud Messaging (foreground + token registration)
    Injected via AdminPanelProvider renderHook('panels::head.end')
    Only rendered when FIREBASE_VAPID_KEY is configured.
--}}
@php
    $vapidKey = config('services.firebase_web.vapid_key');
@endphp

@auth
@if($vapidKey)
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/12.9.0/firebase-app.js';
import { getMessaging, getToken, onMessage } from 'https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging.js';

const firebaseConfig = {
    apiKey:            @json(config('services.firebase_web.api_key')),
    authDomain:        @json(config('services.firebase_web.auth_domain')),
    projectId:         @json(config('services.firebase_web.project_id')),
    storageBucket:     @json(config('services.firebase_web.storage_bucket')),
    messagingSenderId: @json(config('services.firebase_web.messaging_sender_id')),
    appId:             @json(config('services.firebase_web.app_id')),
};

const vapidKey   = @json($vapidKey);
const csrfToken  = @json(csrf_token());
const fcmTokenUrl = '{{ route("admin.web.fcm-token") }}';

(async () => {
    try {
        const app       = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        if (!('serviceWorker' in navigator)) {
            console.warn('[BookMi FCM] Service workers not supported.');
            return;
        }

        // Register background service worker
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

        // Request notification permission (browser prompt, only shown once)
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.info('[BookMi FCM] Notification permission denied.');
            return;
        }

        // Get / refresh FCM token and persist it server-side
        const token = await getToken(messaging, {
            vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (token) {
            await fetch(fcmTokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ token }),
            });
        }

        // --- Foreground message handler ---
        // When the admin panel is open and a push arrives, show a native
        // notification AND refresh the Livewire notification bell.
        onMessage(messaging, (payload) => {
            const title = payload.notification?.title ?? 'BookMi';
            const body  = payload.notification?.body  ?? '';

            // Trigger Livewire bell refresh (re-poll)
            window.Livewire?.dispatch('bookmi:notification-received');

            // Native foreground notification
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body,
                    icon: '/favicon.ico',
                    tag:  'bookmi-fg-notification',
                });
            }
        });

    } catch (err) {
        console.warn('[BookMi FCM] Initialisation error:', err);
    }
})();
</script>
@endif
@endauth
