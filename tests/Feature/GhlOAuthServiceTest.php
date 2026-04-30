<?php

namespace Tests\Feature;

use App\Services\GhlOAuthService;
use Tests\TestCase;

class GhlOAuthServiceTest extends TestCase
{
    public function test_signed_state_is_valid_only_once(): void
    {
        $service = app(GhlOAuthService::class);
        $state = $service->makeAndStoreState();

        $this->assertTrue($service->validateState($state));
        $this->assertFalse($service->validateState($state));
    }

    public function test_state_validation_rejects_tampered_signed_state(): void
    {
        $service = app(GhlOAuthService::class);
        $state = $service->makeAndStoreState();
        $tampered = preg_replace('/.$/', 'x', $state) ?: $state.'x';

        $this->assertFalse($service->validateState($tampered));
    }
}
