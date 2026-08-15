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
        $items = $aboutPerfectOrder->menuItems()->withPivot('quantity')->get()->keyBy('name');

        $this->assertSame(2, (int) $items['Arrosto']->pivot->quantity);
        $this->assertSame(1, (int) $items['Chicken']->pivot->quantity);
        $this->assertSame(1, (int) $items['Lamb']->pivot->quantity);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $aboutPerfectOrder->id,
            'name' => 'Arrosto',
            'quantity' => 2,
        ]);

        $this->assertTrue($restaurant->orders()
            ->where('content', 'Mertilla Di Pollo, Speziato, Selvaggio, Amore, Margherita Amore Speziato')
            ->exists());
    }
}
