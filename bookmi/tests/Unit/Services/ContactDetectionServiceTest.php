<?php

namespace Tests\Unit\Services;

use App\Services\ContactDetectionService;
use Tests\TestCase;

class ContactDetectionServiceTest extends TestCase
{
    private ContactDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContactDetectionService();
    }

    // ── Phone ──────────────────────────────────────────────────────────────────

    public function test_detects_international_phone_number(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Contactez-moi au +225 07 12 34 56 78'));
    }

    public function test_detects_local_phone_number(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Mon numéro: 0708000000'));
    }

    // ── Email ──────────────────────────────────────────────────────────────────

    public function test_detects_email_address(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Envoyez à user@example.com merci'));
    }

    // ── URL ────────────────────────────────────────────────────────────────────

    public function test_detects_http_url(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Voir mon site http://monsite.ci'));
    }

    public function test_detects_https_url(): void
    {
        $this->assertTrue($this->service->containsContactInfo('https://portfolio.example.com'));
    }

    // ── WhatsApp / Telegram ────────────────────────────────────────────────────

    public function test_detects_whatsapp_link(): void
    {
        $this->assertTrue($this->service->containsContactInfo('wa.me/2250700000000'));
    }

    public function test_detects_whatsapp_keyword(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Écrivez-moi sur WhatsApp'));
    }

    public function test_detects_telegram_link(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Rejoignez t.me/moncanal'));
    }

    public function test_detects_telegram_at_handle(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Mon Telegram: @monpseudo123'));
    }

    // ── Social ─────────────────────────────────────────────────────────────────

    public function test_detects_instagram_keyword(): void
    {
        $this->assertTrue($this->service->containsContactInfo('Suivez mon insta pour plus'));
    }

    // ── Clean messages ─────────────────────────────────────────────────────────

    public function test_clean_message_not_flagged(): void
    {
        $this->assertFalse($this->service->containsContactInfo('Bonjour, êtes-vous disponible le 15 mars ?'));
    }

    public function test_clean_message_with_date_not_flagged(): void
    {
        $this->assertFalse($this->service->containsContactInfo('Ma prestation dure 2h pour 150 000 FCFA.'));
    }

    // ── detect() ───────────────────────────────────────────────────────────────

    public function test_detect_returns_matching_keys(): void
    {
        $keys = $this->service->detect('user@test.com et +225 07 00 00 00 00');
        $this->assertContains('email', $keys);
        $this->assertContains('phone', $keys);
    }

    public function test_detect_returns_empty_for_clean_message(): void
    {
        $this->assertEmpty($this->service->detect('Merci pour votre intérêt.'));
    }
}
