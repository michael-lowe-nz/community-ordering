<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScrapingLog>
 */
class ScrapingLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScrapingLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'scraping_type' => $this->faker->randomElement(['manual', 'scheduled']),
            'status' => $this->faker->randomElement(['success', 'failed', 'partial']),
            'items_found' => $this->faker->numberBetween(0, 50),
            'items_created' => $this->faker->numberBetween(0, 30),
            'items_updated' => $this->faker->numberBetween(0, 20),
            'error_message' => $this->faker->optional()->sentence(),
            'scraped_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'duration_seconds' => $this->faker->numberBetween(5, 300),
        ];
    }

    /**
     * Indicate that the scraping was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'error_message' => null,
            'items_found' => $this->faker->numberBetween(10, 50),
            'items_created' => $this->faker->numberBetween(5, 30),
            'items_updated' => $this->faker->numberBetween(0, 20),
        ]);
    }

    /**
     * Indicate that the scraping failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'items_found' => 0,
            'items_created' => 0,
            'items_updated' => 0,
        ]);
    }

    /**
     * Indicate that the scraping was partial.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
            'error_message' => $this->faker->sentence(),
            'items_found' => $this->faker->numberBetween(5, 20),
            'items_created' => $this->faker->numberBetween(1, 10),
            'items_updated' => $this->faker->numberBetween(0, 5),
        ]);
    }

    /**
     * Indicate that the scraping was manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'scraping_type' => 'manual',
        ]);
    }

    /**
     * Indicate that the scraping was scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scraping_type' => 'scheduled',
        ]);
    }
}