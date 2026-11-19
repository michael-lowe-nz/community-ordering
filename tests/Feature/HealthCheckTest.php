<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_route_is_up_when_the_database_is_reachable(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_route_fails_when_the_database_is_unreachable(): void
    {
        config([
            'app.debug' => false,
            'database.connections.unreachable' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 1,
                'database' => 'missing',
                'username' => 'missing',
                'password' => '',
            ],
            'database.default' => 'unreachable',
        ]);
        DB::purge('unreachable');

        $this->get('/up')->assertStatus(500);
    }

    public function test_legacy_health_check_route_still_responds(): void
    {
        $this->get('/health-check')->assertOk()->assertSee('OK');
    }
}
