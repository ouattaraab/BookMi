<?php

namespace App\Services;

/**
 * Detects contact-sharing patterns in message content to prevent disintermediation.
 *
 * Patterns detected:
 *  - Phone numbers (international or local formats)
 *  - Email addresses
 *  - HTTP/HTTPS/WhatsApp/Telegram URLs
 *  - WhatsApp/Telegram/Signal handles or keywords
 */
class ContactDetectionService
{
    /**
     * @var array<string> Regular expression patterns to detect contact info.
     */
    private const PATTERNS = [
        // International/local phone numbers: +225 07 00 00 00 00, 0708000000, 07 08 00 00 00
        'phone'     => '/(?:\+?\d[\d\s\-\(\)]{7,}\d)/',

        // Email addresses
        'email'     => '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',

        // URLs (http, https, ftp)
        'url'       => '/https?:\/\/[^\s]+/',

        // WhatsApp wa.me links or WhatsApp keywords
        'whatsapp'  => '/\bwa\.me\/\S+|\bwhatsapp\b/i',

        // Telegram t.me links or @handle
        'telegram'  => '/\bt\.me\/\S+|\btelegram\b|\B@[a-zA-Z0-9_]{5,}/i',

        // Signal / WeChat / Instagram DM keywords
        'social'    => '/\bsignal\b|\bwechat\b|\binsta(gram)?\b|\bdm\s?me\b/i',
    ];

    /**
     * Checks whether the given text contains any contact-sharing pattern.
     */
    public function containsContactInfo(string $text): bool
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of detected pattern keys for diagnostics (used in tests/admin).
     *
     * @return array<string>
     */
    public function detect(string $text): array
    {
        $detected = [];
        foreach (self::PATTERNS as $key => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $detected[] = $key;
            }
        }

        return $detected;
    }
}
