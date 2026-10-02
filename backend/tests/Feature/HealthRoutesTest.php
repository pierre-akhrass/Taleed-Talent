<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthRoutesTest extends TestCase
{
    public function test_liveness_route_returns_json(): void
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_readiness_checks_database_connectivity(): void
    {
        $this->getJson('/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('database', 'ok');
    }

    public function test_help_and_app_routes_are_separate(): void
    {
        $this->get('/help')->assertOk()->assertSee('Taleed Talent help');
        $this->get('/app')->assertOk()->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
