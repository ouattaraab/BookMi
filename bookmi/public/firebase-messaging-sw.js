/**
 * BookMi — Firebase Cloud Messaging Service Worker
 * Handles background push notifications for the admin panel.
 *
 * Firebase config values are public (client-side) — safe to hardcode here.
 * This file MUST be served from the root path /firebase-messaging-sw.js.
 */

importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey:            'AIzaSyB6LbywHNyRH7qJS7m8UB5j1BMZy0sDlgs',
    authDomain:        'bookmi-ea7a9.firebaseapp.com',
    projectId:         'bookmi-ea7a9',
    storageBucket:     'bookmi-ea7a9.firebasestorage.app',
    messagingSenderId: '372395527538',
    appId:             '1:372395527538:web:1a3894eb332739e8b661eb',
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
