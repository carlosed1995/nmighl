<?php

namespace Database\Seeders;

use App\Models\GhlClient;
use App\Models\GhlInvoice;
use Illuminate\Database\Seeder;

class GhlInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        GhlClient::all()->each(function (GhlClient $client) {
            GhlInvoice::factory()->count(fake()->numberBetween(2, 4))->create([
                'ghl_client_id'   => $client->id,
                'ghl_location_id' => $client->ghl_location_id,
            ]);
        });
    }
}
