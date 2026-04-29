<?php

namespace Database\Seeders;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use Illuminate\Database\Seeder;

class GhlClientSeeder extends Seeder
{
    public function run(): void
    {
        GhlLocation::all()->each(function (GhlLocation $location) {
            GhlClient::factory()->count(3)->create([
                'ghl_location_id' => $location->id,
            ]);
        });
    }
}
