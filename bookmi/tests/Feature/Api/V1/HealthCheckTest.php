<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_200_with_envelope_format(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'version',
                ],
            ]);
    }

    public function test_health_check_returns_correct_data_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'ok',
                    'version' => '1.0.0',
                ],
            ]);
    }
}
