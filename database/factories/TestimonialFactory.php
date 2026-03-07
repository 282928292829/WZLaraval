<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => 'testimonials/'.fake()->uuid().'.jpg',
            'name_ar' => fake()->optional(0.7)->name(),
            'name_en' => fake()->optional(0.7)->name(),
            'quote_ar' => fake()->optional(0.5)->sentence(12),
            'quote_en' => fake()->optional(0.5)->sentence(12),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
