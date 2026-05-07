<?php

namespace Database\Seeders;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Models\NmiLocationCredential;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubaccountTestSeeder extends Seeder
{
    public function run(): void
    {
        $location = GhlLocation::query()->updateOrCreate(
            ['ghl_id' => 'loc-test-subaccount-001'],
            [
                'name' => 'Test Subaccount',
                'company_id' => 'test-company-001',
                'timezone' => 'America/Mexico_City',
                'raw' => ['seeded' => true],
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'tenant-test@example.com'],
            [
                'name' => 'Tenant Test User',
                'role' => 'subaccount_user',
                'ghl_location_id' => $location->ghl_id,
                'password' => 'password',
            ]
        );

        GhlClient::query()->updateOrCreate(
            [
                'ghl_location_id' => $location->id,
                'ghl_contact_id' => 'contact-test-001',
            ],
            [
                'name' => 'Tenant Demo Client',
                'email' => 'client-demo@example.com',
                'phone' => '+15551230001',
                'tags' => ['seed', 'tenant'],
                'raw' => ['seeded' => true],
            ]
        );

        NmiLocationCredential::query()->updateOrCreate(
            ['ghl_location_id' => $location->ghl_id],
            [
                'api_security_key' => 'replace-with-real-nmi-api-key',
                'webhook_signing_key' => 'replace-with-real-nmi-signing-key',
                'webhook_secret' => 'replace-with-real-subscription-secret',
            ]
        );
    }
}
