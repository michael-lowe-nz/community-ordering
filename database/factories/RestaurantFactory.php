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
            'menu_url' => $this->faker->optional()->url(),
            'menu_scraping_enabled' => false,
            'last_menu_scrape' => null,
            'menu_scrape_frequency' => null,
            'scraping_notes' => null,
        ];
    }

    /**
     * Indicate that the restaurant has menu scraping enabled.
     */
    public function withScrapingEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_scraping_enabled' => true,
            'menu_url' => $this->faker->url(),
            'menu_scrape_frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
        ]);
    }

    /**
     * Indicate that the restaurant was recently scraped.
     */
    public function recentlyScrapped(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_scraping_enabled' => true,
            'menu_url' => $this->faker->url(),
            'last_menu_scrape' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'menu_scrape_frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
        ]);
    }

    /**
     * Indicate that the restaurant needs scraping.
     */
    public function needsScraping(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_scraping_enabled' => true,
            'menu_url' => $this->faker->url(),
            'last_menu_scrape' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'menu_scrape_frequency' => 'daily',
        ]);
    }

    /**
     * Set a specific scraping frequency.
     */
    public function withFrequency(string $frequency): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_scraping_enabled' => true,
            'menu_url' => $this->faker->url(),
            'menu_scrape_frequency' => $frequency,
        ]);
    }
}