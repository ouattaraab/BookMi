import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Top-level background message handler (must be a top-level function).
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Firebase is already initialized when this runs.
  // Background messages are shown automatically by FCM on Android.
}

class NotificationService {
  NotificationService._();
  static final NotificationService instance = NotificationService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  /// Called when user taps a notification — override to handle navigation.
  void Function(RemoteMessage message)? onNotificationTap;

  Future<void> init() async {
    // Register background handler
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    // Request permission (iOS + Android 13+)
    await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Configure local notifications for foreground display
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings();
    await _localNotifications.initialize(
      const InitializationSettings(android: androidInit, iOS: iosInit),
      onDidReceiveNotificationResponse: (details) {
        // Foreground notification tap — payload is JSON of the RemoteMessage data
        // handled via onNotificationTap callback
      },
    );

    // Create notification channel for Android
    const channel = AndroidNotificationChannel(
      'bookmi_channel',
      'BookMi Notifications',
      description: 'Notifications de réservation BookMi',
      importance: Importance.high,
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);

    // Foreground message handler
    FirebaseMessaging.onMessage.listen((message) {
      _showForegroundNotification(message);
    });

    // Notification opened app from background
    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      onNotificationTap?.call(message);
    });

    // App opened from terminated state via notification
    final initial = await _messaging.getInitialMessage();
    if (initial != null) {
      // Delay to allow app to finish initializing
      Future.delayed(const Duration(milliseconds: 500), () {
        onNotificationTap?.call(initial);
      });
    }

    // Token refresh
    _messaging.onTokenRefresh.listen((token) {
      _onTokenRefresh?.call(token);
    });
  }

  void Function(String token)? _onTokenRefresh;

  void setTokenRefreshCallback(void Function(String token) callback) {
    _onTokenRefresh = callback;
  }

  Future<String?> getFcmToken() async {
    try {
      return await _messaging.getToken();
    } catch (_) {
      return null;
    }
  }

  void _showForegroundNotification(RemoteMessage message) {
    final notification = message.notification;
    if (notification == null) return;

    _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          'bookmi_channel',
          'BookMi Notifications',
          channelDescription: 'Notifications de réservation BookMi',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
    );
  }
}
