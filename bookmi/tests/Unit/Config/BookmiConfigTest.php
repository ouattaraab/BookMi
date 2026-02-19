<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

class BookmiConfigTest extends TestCase
{
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = require __DIR__ . '/../../../config/bookmi.php';
    }

    public function test_commission_rate_is_configured(): void
    {
        $this->assertArrayHasKey('commission_rate', $this->config);
        $this->assertEquals(15, $this->config['commission_rate']);
    }

    public function test_escrow_settings_are_configured(): void
    {
        $this->assertArrayHasKey('escrow', $this->config);
        $this->assertArrayHasKey('auto_confirm_hours', $this->config['escrow']);
        $this->assertArrayHasKey('payout_delay_hours', $this->config['escrow']);
        $this->assertEquals(48, $this->config['escrow']['auto_confirm_hours']);
        $this->assertEquals(24, $this->config['escrow']['payout_delay_hours']);
    }

    public function test_auth_settings_are_configured(): void
    {
        $this->assertArrayHasKey('auth', $this->config);
        $this->assertArrayHasKey('token_expiration_hours', $this->config['auth']);
        $this->assertArrayHasKey('otp_expiration_minutes', $this->config['auth']);
        $this->assertArrayHasKey('max_login_attempts', $this->config['auth']);
        $this->assertArrayHasKey('lockout_minutes', $this->config['auth']);
        $this->assertArrayHasKey('otp_max_resend_per_hour', $this->config['auth']);
    }

    public function test_rate_limits_are_configured(): void
    {
        $this->assertArrayHasKey('rate_limits', $this->config);
        $this->assertArrayHasKey('authenticated', $this->config['rate_limits']);
        $this->assertArrayHasKey('unauthenticated', $this->config['rate_limits']);
        $this->assertArrayHasKey('payment', $this->config['rate_limits']);
        $this->assertArrayHasKey('auth_endpoints', $this->config['rate_limits']);
    }

    public function test_talent_levels_are_configured(): void
    {
        $this->assertArrayHasKey('talent', $this->config);
        $this->assertArrayHasKey('levels', $this->config['talent']);
        $this->assertArrayHasKey('nouveau', $this->config['talent']['levels']);
        $this->assertArrayHasKey('confirme', $this->config['talent']['levels']);
        $this->assertArrayHasKey('populaire', $this->config['talent']['levels']);
        $this->assertArrayHasKey('elite', $this->config['talent']['levels']);
    }

    public function test_cancellation_settings_are_configured(): void
    {
        $this->assertArrayHasKey('cancellation', $this->config);
        $this->assertArrayHasKey('full_refund_days', $this->config['cancellation']);
        $this->assertArrayHasKey('partial_refund_days', $this->config['cancellation']);
        $this->assertArrayHasKey('partial_refund_rate', $this->config['cancellation']);
    }

    public function test_payment_gateways_are_configured(): void
    {
        $this->assertArrayHasKey('payment', $this->config);
        $this->assertEquals('paystack', $this->config['payment']['primary_gateway']);
        $this->assertEquals('fedapay', $this->config['payment']['fallback_gateway']);
    }

    public function test_media_settings_are_configured(): void
    {
        $this->assertArrayHasKey('media', $this->config);
        $this->assertArrayHasKey('max_image_size_mb', $this->config['media']);
        $this->assertArrayHasKey('max_video_size_mb', $this->config['media']);
        $this->assertArrayHasKey('image_format', $this->config['media']);
    }
}
