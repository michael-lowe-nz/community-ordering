<?php

namespace Tests\Feature;

use App\Livewire\MenuItemPicker;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuItemPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_catalog_items(): void
    {
        [$restaurant, $menu] = $this->restaurantWithMenu();
        MenuItem::factory()->create(['menu_id' => $menu->id, 'name' => 'Mapo Tofu']);
        MenuItem::factory()->create(['menu_id' => $menu->id, 'name' => 'Fried Rice']);
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(MenuItemPicker::class, compact('restaurant', 'order'))
            ->set('query', 'mapo')
            ->assertSee('Mapo Tofu')
            ->assertDontSee('Fried Rice');
    }

    public function test_selecting_a_catalog_item_creates_and_increments_the_pivot(): void
    {
        [$restaurant, $menu] = $this->restaurantWithMenu();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);
        $component = Livewire::test(MenuItemPicker::class, compact('restaurant', 'order'));

        $component->call('selectMenuItem', $item->id)->assertDispatched('item-added');
        $component->call('selectMenuItem', $item->id);

        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_item_id' => $item->id, 'quantity' => 2]);
    }

    public function test_unmatched_query_creates_an_ad_hoc_order_item(): void
    {
        [$restaurant, $menu] = $this->restaurantWithMenu();
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(MenuItemPicker::class, compact('restaurant', 'order'))
            ->set('query', 'Chef special')
            ->call('addAdHocItem')
            ->assertDispatched('item-added');

        $menuItem = MenuItem::where('name', 'Chef special')->firstOrFail();
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_item_id' => $menuItem->id, 'quantity' => 1]);
    }

    /** @return array{Restaurant, Menu} */
    private function restaurantWithMenu(): array
    {
        $restaurant = Restaurant::factory()->create();

        return [$restaurant, Menu::factory()->create(['restaurant_id' => $restaurant->id])];
    }
}
