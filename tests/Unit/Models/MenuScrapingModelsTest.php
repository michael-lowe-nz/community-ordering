<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuScrapingModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_has_menus_relationship()
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertTrue($restaurant->menus->contains($menu));
    }

    public function test_restaurant_has_active_menus_relationship()
    {
        $restaurant = Restaurant::factory()->create();
        $activeMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'is_active' => true
        ]);
        $inactiveMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'is_active' => false
        ]);

        $activeMenus = $restaurant->activeMenus;
        $this->assertTrue($activeMenus->contains($activeMenu));
        $this->assertFalse($activeMenus->contains($inactiveMenu));
    }

    public function test_restaurant_has_scraping_logs_relationship()
    {
        $restaurant = Restaurant::factory()->create();
        $log = ScrapingLog::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertTrue($restaurant->scrapingLogs->contains($log));
    }

    public function test_menu_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertEquals($restaurant->id, $menu->restaurant->id);
    }

    public function test_menu_has_menu_items_relationship()
    {
        $menu = Menu::factory()->create();
        $menuItem = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->assertTrue($menu->menuItems->contains($menuItem));
    }

    public function test_menu_item_belongs_to_menu()
    {
        $menu = Menu::factory()->create();
        $menuItem = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->assertEquals($menu->id, $menuItem->menu->id);
    }

    public function test_scraping_log_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $log = ScrapingLog::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertEquals($restaurant->id, $log->restaurant->id);
    }

    public function test_restaurant_scraping_enabled_scope()
    {
        $enabledRestaurant = Restaurant::factory()->create(['menu_scraping_enabled' => true]);
        $disabledRestaurant = Restaurant::factory()->create(['menu_scraping_enabled' => false]);

        $scrapingEnabled = Restaurant::scrapingEnabled()->get();

        $this->assertTrue($scrapingEnabled->contains($enabledRestaurant));
        $this->assertFalse($scrapingEnabled->contains($disabledRestaurant));
    }

    public function test_menu_active_scope()
    {
        $activeMenu = Menu::factory()->create(['is_active' => true]);
        $inactiveMenu = Menu::factory()->create(['is_active' => false]);

        $activeMenus = Menu::active()->get();

        $this->assertTrue($activeMenus->contains($activeMenu));
        $this->assertFalse($activeMenus->contains($inactiveMenu));
    }

    public function test_menu_item_available_scope()
    {
        $availableItem = MenuItem::factory()->create(['is_available' => true]);
        $unavailableItem = MenuItem::factory()->create(['is_available' => false]);

        $availableItems = MenuItem::available()->get();

        $this->assertTrue($availableItems->contains($availableItem));
        $this->assertFalse($availableItems->contains($unavailableItem));
    }
}