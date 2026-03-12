<?php

return [
    'commission_rate' => 15, // Pourcentage frais BookMi (plateforme Côte d'Ivoire)

    'consent' => [
        'cgu_version'     => '2026-03-05',
        'privacy_version' => '2026-03-05',
    ],

    'escrow' => [
        'auto_confirm_hours' => 48, // Heures avant confirmation auto
        'payout_delay_hours' => 24, // Délai versement talent
    ],

    'auth' => [
        'token_expiration_hours' => 24,
        'otp_expiration_minutes' => 10,
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'otp_max_resend_per_hour' => 3,
        // Must be set via PASSWORD_RESET_URL env var in production.
        // Defaults to localhost — emails will be broken if this env var is missing.
        'password_reset_url' => env('PASSWORD_RESET_URL', 'http://localhost:3000/reset-password'),
    ],

    'talent' => [
        'low_rating_threshold' => 3.0,
        'levels' => [
            'nouveau' => ['min_bookings' => 0, 'min_rating' => 0],
            'confirme' => ['min_bookings' => 6, 'min_rating' => 3.5],
            'populaire' => ['min_bookings' => 21, 'min_rating' => 4.0],
            'elite' => ['min_bookings' => 51, 'min_rating' => 4.5],
        ],
    ],

    'cancellation' => [
        'full_refund_days' => 14,
        'partial_refund_days' => 7,
        'partial_refund_rate' => 50,
        'mediation_only_days' => 2,
    ],

    'payment' => [
        'primary_gateway'  => 'paystack',
        'fallback_gateway' => 'fedapay',
        // Must be set via PAYMENT_CALLBACK_URL env var in production.
        'callback_url'     => env('PAYMENT_CALLBACK_URL', 'http://localhost:8000/api/v1/payments/callback'),
    ],

    'rate_limits' => [
        'authenticated' => 60,
        'unauthenticated' => 30,
        'payment' => 10,
        'auth_endpoints' => 5, // [H6] Reduced from 10 to 5/min per security audit
    ],

    'media' => [
        'max_image_size_mb' => 10,
        'max_video_size_mb' => 50,
        'image_format' => 'webp',
    ],

    'reminders' => [
        'days_before' => [7, 2],
    ],

    'deeplink' => [
        // Android App Links — SHA-256 of release keystore (keytool -list -v -keystore keystore.jks)
        'android_sha256' => env('ANDROID_SHA256_FINGERPRINT', ''),
        // iOS Universal Links — format: TEAMID.com.bookmi.app
        'ios_app_id'     => env('IOS_APP_ID', 'TEAMID.click.bookmi.app'),
    ],

    'admin' => [
        'pending_action_reminder_hours' => 48,
        'escalation_hours' => 96,
    ],

    'admin_path' => env('ADMIN_PATH', 'admin'),

    'admin_allowed_ips' => env('ADMIN_ALLOWED_IPS', ''),

    'verification' => [
        'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_file_size_kb' => 5120,
        'disk' => 'identity_documents',
        'document_types' => ['cni', 'passport'],
    ],

    'sms' => [
        'enabled'       => env('ORANGE_SMS_ENABLED', false),
        'client_id'     => env('ORANGE_CLIENT_ID', ''),
        'client_secret' => env('ORANGE_CLIENT_SECRET', ''),
        // country_sender_number sans + ni 00 (ex. 2250000 pour Côte d'Ivoire)
        'sender_number' => env('ORANGE_SENDER_NUMBER', '2250000'),
        // Sender name approuvé par Orange (max 11 caractères alphanumériques)
        'sender_name'   => env('ORANGE_SENDER_NAME', 'BookMi'),
    ],
];
