<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_has_many_menus()
    {
        $restaurant = Restaurant::factory()->create();
        $menus = Menu::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

        $this->assertCount(3, $restaurant->menus);
        $this->assertInstanceOf(Menu::class, $restaurant->menus->first());
    }

    public function test_active_menus_returns_only_active_menus()
    {
        $restaurant = Restaurant::factory()->create();
        Menu::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true]);
        Menu::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => false]);

        $activeMenus = $restaurant->activeMenus;

        $this->assertCount(1, $activeMenus);
        $this->assertTrue($activeMenus->first()->is_active);
    }

    public function test_restaurant_has_many_scraping_logs()
    {
        $restaurant = Restaurant::factory()->create();
        $logs = ScrapingLog::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);

        $this->assertCount(2, $restaurant->scrapingLogs);
        $this->assertInstanceOf(ScrapingLog::class, $restaurant->scrapingLogs->first());
    }

    public function test_scraping_enabled_scope_returns_only_enabled_restaurants()
    {
        Restaurant::factory()->create(['menu_scraping_enabled' => true]);
        Restaurant::factory()->create(['menu_scraping_enabled' => false]);

        $enabledRestaurants = Restaurant::scrapingEnabled()->get();

        $this->assertCount(1, $enabledRestaurants);
        $this->assertTrue($enabledRestaurants->first()->menu_scraping_enabled);
    }

    public function test_needs_scraping_scope_returns_restaurants_needing_scraping()
    {
        // Restaurant that has never been scraped
        Restaurant::factory()->create([
            'menu_scraping_enabled' => true,
            'last_menu_scrape' => null,
            'menu_scrape_frequency' => 'daily'
        ]);

        // Restaurant scraped yesterday with daily frequency
        Restaurant::factory()->create([
            'menu_scraping_enabled' => true,
            'last_menu_scrape' => now()->subDays(2),
            'menu_scrape_frequency' => 'daily'
        ]);

        // Restaurant scraped today (shouldn't need scraping)
        Restaurant::factory()->create([
            'menu_scraping_enabled' => true,
            'last_menu_scrape' => now(),
            'menu_scrape_frequency' => 'daily'
        ]);

        $needsScrapingRestaurants = Restaurant::needsScraping()->get();

        $this->assertCount(2, $needsScrapingRestaurants);
    }

    public function test_restaurant_casts_attributes_correctly()
    {
        $restaurant = Restaurant::factory()->create([
            'menu_scraping_enabled' => 1,
            'last_menu_scrape' => '2024-01-01 12:00:00'
        ]);

        $this->assertIsBool($restaurant->menu_scraping_enabled);
        $this->assertInstanceOf(\Carbon\Carbon::class, $restaurant->last_menu_scrape);
    }
}