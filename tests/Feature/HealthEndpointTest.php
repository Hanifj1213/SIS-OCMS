<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200_ok(): void
    {
        $response = $this->getJson('/up');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok'])
            ->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_health_endpoint_contains_iso8601_timestamp(): void
    {
        $response = $this->getJson('/up');

        $data = $response->json();

        $this->assertNotEmpty($data['timestamp']);
        // ISO 8601 format check
        $this->assertNotFalse(
            \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['timestamp']),
            'Timestamp should be valid ISO 8601 format'
        );
    }

    public function test_health_endpoint_accessible_without_auth(): void
    {
        // No actingAs — verify unauthenticated access
        $response = $this->getJson('/up');

        $response->assertStatus(200);
    }

    public function test_health_endpoint_does_not_leak_sensitive_data(): void
    {
        $response = $this->getJson('/up');

        $content = $response->getContent();

        // Should not contain database credentials, APP_KEY, or error details
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('app_key', strtolower($content));
        $this->assertStringNotContainsString('base64:', $content);
    }
}
