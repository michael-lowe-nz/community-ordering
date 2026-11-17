<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Database\Seeders\PickleJarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickleJarSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickle_jar_seeder_replaces_old_menu_data_with_requested_items(): void
    {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Pickle Jar Karori',
        ]);

        $menu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Main Menu',
        ]);

        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Old Item',
            'is_available' => true,
        ]);

        $this->seed(PickleJarSeeder::class);

        $restaurant = Restaurant::where('name', 'like', '%Pickle Jar%')->firstOrFail();
        $menu = $restaurant->menus()->firstOrFail();

        $this->assertContains('Arrosto', $menu->menuItems()->pluck('name')->all());
        $this->assertContains('Pizza', $menu->menuItems()->pluck('name')->all());
        $this->assertContains('Chips', $menu->menuItems()->pluck('name')->all());
        $this->assertContains('Beauchamp', $menu->menuItems()->pluck('name')->all());
        $this->assertDatabaseMissing('menu_items', [
            'menu_id' => $menu->id,
            'name' => 'Old Item',
        ]);

        $aboutPerfectOrder = $restaurant->orders()->where('content', 'About perfect')->firstOrFail();
        $arrostoItem = $menu->menuItems()->where('name', 'Arrosto')->firstOrFail();
        $chickenItem = $menu->menuItems()->where('name', 'Chicken')->firstOrFail();
        $lambItem = $menu->menuItems()->where('name', 'Lamb')->firstOrFail();

        $this->assertDatabaseHas('order_items', ['order_id' => $aboutPerfectOrder->id, 'menu_item_id' => $arrostoItem->id, 'quantity' => 2]);
        $this->assertDatabaseHas('order_items', ['order_id' => $aboutPerfectOrder->id, 'menu_item_id' => $chickenItem->id, 'quantity' => 1]);
        $this->assertDatabaseHas('order_items', ['order_id' => $aboutPerfectOrder->id, 'menu_item_id' => $lambItem->id, 'quantity' => 1]);

        $this->assertTrue($restaurant->orders()
            ->where('content', 'Mertilla Di Pollo, Speziato, Selvaggio, Amore, Margherita Amore Speziato')
            ->exists());
    }
}
