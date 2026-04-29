<?php

namespace Database\Factories;

use App\Models\GhlLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GhlLocation>
 */
class GhlLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ghl_id'     => fake()->unique()->lexify('loc_??????????'),
            'name'       => fake()->company(),
            'company_id' => fake()->optional()->lexify('comp_????????'),
            'timezone'   => fake()->randomElement([
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'America/Phoenix',
            ]),
        ];
    }
}
