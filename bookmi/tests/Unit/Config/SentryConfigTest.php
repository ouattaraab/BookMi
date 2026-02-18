<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

class SentryConfigTest extends TestCase
{
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = require __DIR__ . '/../../../config/sentry.php';
    }

    public function test_sentry_dsn_defaults_to_null(): void
    {
        $this->assertNull($this->config['dsn']);
    }

    public function test_sentry_config_has_required_keys(): void
    {
        $this->assertArrayHasKey('dsn', $this->config);
        $this->assertArrayHasKey('release', $this->config);
        $this->assertArrayHasKey('environment', $this->config);
        $this->assertArrayHasKey('sample_rate', $this->config);
        $this->assertArrayHasKey('breadcrumbs', $this->config);
        $this->assertArrayHasKey('tracing', $this->config);
    }

    public function test_sentry_ignores_health_check_transaction(): void
    {
        $this->assertContains('/up', $this->config['ignore_transactions']);
    }
}
