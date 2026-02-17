<?php

return [
    'commission_rate' => 15, // Pourcentage frais BookMi

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
    ],

    'talent' => [
        'low_rating_threshold' => 3.0,
        'levels' => [
            'nouveau' => ['min_bookings' => 0, 'min_rating' => 0],
            'confirme' => ['min_bookings' => 6, 'min_rating' => 3.5],
            'premium' => ['min_bookings' => 21, 'min_rating' => 4.0],
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
        'primary_gateway' => 'paystack',
        'fallback_gateway' => 'cinetpay',
    ],

    'rate_limits' => [
        'authenticated' => 60,
        'unauthenticated' => 30,
        'payment' => 10,
        'auth_endpoints' => 10,
    ],

    'media' => [
        'max_image_size_mb' => 10,
        'max_video_size_mb' => 50,
        'image_format' => 'webp',
    ],

    'reminders' => [
        'days_before' => [7, 2],
    ],

    'admin' => [
        'pending_action_reminder_hours' => 48,
        'escalation_hours' => 96,
    ],

    'verification' => [
        'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_file_size_kb' => 5120,
        'disk' => 'identity_documents',
        'document_types' => ['cni', 'passport'],
    ],
];
