<?php

namespace Database\Factories;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GhlClient>
 */
class GhlClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ghl_location_id' => GhlLocation::factory(),
            'ghl_contact_id'  => fake()->unique()->lexify('cntct_??????????'),
            'name'            => fake()->name(),
            'email'           => fake()->unique()->safeEmail(),
            'phone'           => fake()->phoneNumber(),
            'tags'            => fake()->randomElements(['VIP', 'Recurring', 'New', 'Partner', 'Trial'], fake()->numberBetween(0, 2)),
            'last_activity_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
