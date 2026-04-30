<?php

namespace Tests\Feature;

use App\Services\GhlOAuthService;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class GhlOAuthCallbackTest extends TestCase
{
    public function test_marketplace_callback_without_state_exchanges_code(): void
    {
        $service = Mockery::mock(GhlOAuthService::class);
        $service->shouldReceive('exchangeCodeForToken')->once()->with('abc123');
        $this->app->instance(GhlOAuthService::class, $service);

        $response = $this->get('/oauth/callback?code=abc123');

        $response->assertRedirect(route('clients'));
        $response->assertSessionHas('status', 'OAuth connected successfully. You can now sync.');
    }

    public function test_app_initiated_callback_rejects_invalid_state(): void
    {
        $service = Mockery::mock(GhlOAuthService::class);
        $service->shouldReceive('validateState')->once()->with('invalid')->andReturnFalse();
        $service->shouldReceive('exchangeCodeForToken')->never();
        $this->app->instance(GhlOAuthService::class, $service);

        $response = $this->withSession([
            'ghl_oauth_state' => 'expected-state',
        ])->get('/oauth/callback?code=abc123&state=invalid');

        $response->assertRedirect(route('clients'));
        $response->assertSessionHas('error', 'Invalid OAuth state.');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('clients')) {
            Route::get('/clients', fn () => 'clients')->name('clients');
        }
    }
}
