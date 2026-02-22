<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => strtoupper($this->faker->bothify('ORD-######')),
            'user_id' => \App\Models\User::factory(),
            'status' => 'pending',
            'layout_option' => '1',
            'subtotal' => 0,
            'total_amount' => 0,
            'currency' => 'SAR',
        ];
    }
}
