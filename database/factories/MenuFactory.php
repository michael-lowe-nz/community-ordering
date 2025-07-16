<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => $this->faker->randomElement(['Main Menu', 'Lunch Menu', 'Dinner Menu', 'Drinks Menu']),
            'menu_type' => $this->faker->randomElement(['scraped', 'manual', 'seasonal']),
            'is_active' => true,
            'scraped_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'source_url' => $this->faker->optional()->url(),
        ];
    }

    /**
     * Indicate that the menu is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the menu is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the menu is scraped.
     */
    public function scraped(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_type' => 'scraped',
            'scraped_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'source_url' => $this->faker->url(),
        ]);
    }

    /**
     * Indicate that the menu is manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_type' => 'manual',
            'scraped_at' => null,
            'source_url' => null,
        ]);
    }
}