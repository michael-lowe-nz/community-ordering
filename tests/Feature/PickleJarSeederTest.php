<?php

namespace Tests\Feature;

use App\Livewire\RestaurantView;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\PickleJarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PickleJarSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_menu_with_pizzas_sold_by_the_metre(): void
    {
        $this->seed(PickleJarSeeder::class);

        $menu = Restaurant::where('name', 'like', '%Pickle Jar%')->firstOrFail()->menus()->firstOrFail();

        $amore = $menu->menuItems()->where('name', 'Amore')->firstOrFail();
        $this->assertSame(MenuItem::UNIT_METRES, $amore->unit);
        $this->assertSame(0.25, $amore->unit_step);
        $this->assertSame(0.5, $amore->unit_start);

        $this->assertNull($menu->menuItems()->where('name', 'Fries')->firstOrFail()->unit);
        $this->assertSame(['Half', 'Full'], $menu->menuItems()->where('name', 'Korean Wings')->firstOrFail()->variants);
    }

    public function test_it_seeds_every_order_from_the_notes_with_its_metres_of_pizza(): void
    {
        $this->seed(PickleJarSeeder::class);

        $orders = Order::with('items.menuItem')->orderBy('id')->get();

        $this->assertCount(24, $orders);

        $first = $orders->first();
        $this->assertSame('Pickle Jar notes #1', $first->content);
        $this->assertSame(8, $first->participant_count);
        $this->assertSame('just_right', $first->verdict);
        $this->assertSame('About perfect.', $first->notes);
        $this->assertSame(2.0, $first->pizza_metres);

        $fourteen = $orders->firstWhere('participant_count', 14);
        $this->assertSame(3.5, $fourteen->pizza_metres);
        $this->assertCount(14, $fourteen->attendees);

        $halfAndHalf = $orders->firstWhere('content', 'Pickle Jar notes #13');
        $this->assertSame(2.0, $halfAndHalf->pizza_metres);
        $this->assertSame(0.25, $halfAndHalf->items->firstWhere('name', 'Margherita')->quantity);

        $wings = $orders->firstWhere('content', 'Pickle Jar notes #21');
        $this->assertSame(2.5, $wings->pizza_metres);
        $this->assertSame('Full', $wings->items->firstWhere('name', 'Korean Wings')->variant);
    }

    public function test_running_it_again_replaces_its_orders_but_keeps_orders_made_in_the_app(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Pickle Jar Karori']);
        $menu = Menu::factory()->for($restaurant)->create(['name' => 'Main Menu']);
        $staleItem = MenuItem::factory()->for($menu)->create(['name' => 'Pizza']);
        $usedItem = MenuItem::factory()->for($menu)->create(['name' => 'Garlic Bread']);

        $oldSeededOrder = Order::factory()->for($restaurant)->create(['user_id' => null]);
        $oldSeededOrder->items()->create(['menu_item_id' => $staleItem->id, 'quantity' => 2]);

        $appOrder = Order::factory()->for($restaurant)->create(['user_id' => User::factory()]);
        $appOrder->items()->create(['menu_item_id' => $usedItem->id, 'quantity' => 1]);

        $this->seed(PickleJarSeeder::class);
        $this->seed(PickleJarSeeder::class);

        $this->assertSame(1, Restaurant::where('name', 'like', '%Pickle Jar%')->count());
        $this->assertSame(24, $restaurant->orders()->whereNull('user_id')->count());
        $this->assertModelMissing($oldSeededOrder);
        $this->assertModelMissing($staleItem);
        $this->assertModelExists($appOrder);
        $this->assertModelExists($usedItem);
        $this->assertSame(1, $menu->menuItems()->where('name', 'Amore')->count());
    }

    public function test_guests_see_the_metres_ordered_but_not_who_was_there(): void
    {
        $this->seed(PickleJarSeeder::class);
        $restaurant = Restaurant::where('name', 'like', '%Pickle Jar%')->firstOrFail();
        $order = $restaurant->orders()->where('participant_count', 14)->firstOrFail();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('3.5m pizza')
            ->assertSee('0.25m each')
            ->assertSee('Homewood')
            ->assertDontSee('Annaleise');

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSee('Pickle Jar notes #24')
            ->assertSee('Too much, maybe should have had…')
            ->assertSee('Pickle Jar notes #16')
            ->assertDontSee('Annaleise');
    }

    public function test_signed_in_users_see_who_was_there(): void
    {
        $this->seed(PickleJarSeeder::class);
        $order = Order::where('participant_count', 14)->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Who:')
            ->assertSee('Annaleise');
    }
}
