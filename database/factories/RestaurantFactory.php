<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Restaurant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Restaurant',
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'cuisine_type' => $this->faker->randomElement(['Italian', 'Chinese', 'Mexican', 'American', 'French', 'Japanese']),
            'price_range' => $this->faker->randomElement(['$', '$$', '$$$', '$$$$']),
            'opening_hours' => 'Mon-Sun: 9:00 AM - 10:00 PM',
            'description' => $this->faker->paragraph(),
            'website' => $this->faker->optional()->url(),
            'google_place_id' => $this->faker->uuid(),
        ];
    }
}