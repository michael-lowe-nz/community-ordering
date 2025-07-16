<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertInstanceOf(Restaurant::class, $menu->restaurant);
        $this->assertEquals($restaurant->id, $menu->restaurant->id);
    }

    public function test_menu_has_many_menu_items()
    {
        $menu = Menu::factory()->create();
        $menuItems = MenuItem::factory()->count(3)->create(['menu_id' => $menu->id]);

        $this->assertCount(3, $menu->menuItems);
        $this->assertInstanceOf(MenuItem::class, $menu->menuItems->first());
    }

    public function test_active_menu_items_scope_returns_only_available_items()
    {
        $menu = Menu::factory()->create();
        MenuItem::factory()->create(['menu_id' => $menu->id, 'is_available' => true]);
        MenuItem::factory()->create(['menu_id' => $menu->id, 'is_available' => false]);

        $activeItems = $menu->activeMenuItems;

        $this->assertCount(1, $activeItems);
        $this->assertTrue($activeItems->first()->is_available);
    }

    public function test_active_scope_returns_only_active_menus()
    {
        Menu::factory()->create(['is_active' => true]);
        Menu::factory()->create(['is_active' => false]);

        $activeMenus = Menu::active()->get();

        $this->assertCount(1, $activeMenus);
        $this->assertTrue($activeMenus->first()->is_active);
    }

    public function test_scraped_scope_returns_only_scraped_menus()
    {
        Menu::factory()->create(['menu_type' => 'scraped']);
        Menu::factory()->create(['menu_type' => 'manual']);

        $scrapedMenus = Menu::scraped()->get();

        $this->assertCount(1, $scrapedMenus);
        $this->assertEquals('scraped', $scrapedMenus->first()->menu_type);
    }

    public function test_menu_casts_attributes_correctly()
    {
        $menu = Menu::factory()->create([
            'is_active' => 1,
            'scraped_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertIsBool($menu->is_active);
        $this->assertInstanceOf(\Carbon\Carbon::class, $menu->scraped_at);
    }
}