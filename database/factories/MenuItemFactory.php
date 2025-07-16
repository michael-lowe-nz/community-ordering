<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MenuItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sections = ['appetizers', 'mains', 'desserts', 'beverages', 'sides'];
        $foodNames = [
            'Caesar Salad', 'Grilled Chicken', 'Fish and Chips', 'Pasta Carbonara',
            'Beef Burger', 'Margherita Pizza', 'Chocolate Cake', 'Coffee', 'French Fries'
        ];

        return [
            'menu_id' => Menu::factory(),
            'name' => $this->faker->randomElement($foodNames),
            'description' => $this->faker->optional()->sentence(),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'section' => $this->faker->randomElement($sections),
            'order_index' => $this->faker->numberBetween(1, 100),
            'is_available' => true,
        ];
    }

    /**
     * Indicate that the menu item is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    /**
     * Indicate that the menu item is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Indicate that the menu item has no price.
     */
    public function withoutPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }

    /**
     * Set a specific section for the menu item.
     */
    public function inSection(string $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section' => $section,
        ]);
    }
}
