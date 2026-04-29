<?php

namespace Database\Factories;

use App\Models\GhlClient;
use App\Models\GhlInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GhlInvoice>
 */
class GhlInvoiceFactory extends Factory
{
    public function definition(): array
    {
        $client = GhlClient::factory()->create();
        $issuedDate = fake()->dateTimeBetween('-90 days', '-5 days');

        return [
            'ghl_client_id'   => $client->id,
            'ghl_location_id' => $client->ghl_location_id,
            'invoice_number'  => 'INV-' . fake()->unique()->numerify('####'),
            'issued_date'     => $issuedDate,
            'due_date'        => fake()->dateTimeBetween($issuedDate, '+30 days'),
            'amount'          => fake()->randomFloat(2, 50, 5000),
            'status'          => fake()->randomElement(['draft', 'sent', 'paid', 'pending', 'overdue']),
        ];
    }
}
