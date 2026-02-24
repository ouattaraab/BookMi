{{--
    BookMi — Firebase Cloud Messaging Service Worker
    Rendu côté serveur et servi comme application/javascript via :
        Route::get('/firebase-messaging-sw.js', ...)
    Les valeurs de config sont injectées depuis .env — rien n'est hardcodé.
--}}
importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey:            @json(config('services.firebase_web.api_key')),
    authDomain:        @json(config('services.firebase_web.auth_domain')),
    projectId:         @json(config('services.firebase_web.project_id')),
    storageBucket:     @json(config('services.firebase_web.storage_bucket')),
    messagingSenderId: @json(config('services.firebase_web.messaging_sender_id')),
    appId:             @json(config('services.firebase_web.app_id')),
});

const messaging = firebase.messaging();

/**
 * Display notification when app is in background or closed.
 */
messaging.onBackgroundMessage((payload) => {
    const title = payload.notification?.title ?? 'BookMi';
    const body  = payload.notification?.body  ?? '';

    self.registration.showNotification(title, {
        body,
        icon:  '/favicon.ico',
        badge: '/favicon.ico',
        data:  payload.data ?? {},
        tag:   'bookmi-notification',
        renotify: true,
    });
});

/**
 * Navigate to the relevant admin URL on notification click.
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data      = event.notification.data ?? {};
    const bookingId = data.booking_id;
    let targetUrl   = '/admin';

    if (bookingId) {
        targetUrl = `/admin/bookings/${bookingId}`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes('/admin') && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
