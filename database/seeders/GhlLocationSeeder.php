<?php

namespace Database\Seeders;

use App\Models\GhlLocation;
use Illuminate\Database\Seeder;

class GhlLocationSeeder extends Seeder
{
    public function run(): void
    {
        GhlLocation::factory()->count(5)->create();
    }
}
