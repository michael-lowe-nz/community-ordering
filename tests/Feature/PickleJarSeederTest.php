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

        $this->assertSame([
            'Fries',
            'Onion Rings',
            'Arrosto',
            'Beauchamp',
            'Jalapeno Bacon',
        ], $menu->menuItems()->orderBy('order_index')->pluck('name')->all());

        $this->assertDatabaseMissing('menu_items', [
            'menu_id' => $menu->id,
            'name' => 'Old Item',
        ]);
    }
}
