<?php

namespace Tests\Feature;

use App\Livewire\OrderShow;
use App\Livewire\RestaurantView;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantAndOrderPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_view_renders_recent_orders_as_receipts(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'The Noodle House']);
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Mapo tofu']);
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 2, 'content' => 'Friday dinner']);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 1]);

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSee('The Noodle House')
            ->assertDontSee('How much to order')
            ->assertSee('Friday dinner')
            ->assertSee('Mapo tofu')
            ->assertSee('2 people')
            ->assertSee(route('orders.show', $order), false);
    }

    public function test_party_size_filter_narrows_the_order_list(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $smallDish = MenuItem::factory()->for($menu)->create(['name' => 'Small party dish']);
        $largeDish = MenuItem::factory()->for($menu)->create(['name' => 'Large party dish']);
        $smallOrder = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $largeOrder = Order::factory()->for($restaurant)->create(['participant_count' => 8]);
        $smallOrder->items()->create(['menu_item_id' => $smallDish->id, 'quantity' => 1]);
        $largeOrder->items()->create(['menu_item_id' => $largeDish->id, 'quantity' => 1]);

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->set('partySizeFilter', 2)
            ->assertSee('Small party dish')
            ->assertDontSee('Large party dish');
    }

    public function test_restaurant_view_leaves_out_orders_with_no_dishes(): void
    {
        $restaurant = Restaurant::factory()->create();
        Order::factory()->for($restaurant)->create(['content' => 'Abandoned order']);

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertDontSee('Abandoned order')
            ->assertSee('No orders found.');
    }

    public function test_guests_are_asked_to_log_in_to_start_an_order(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->get(route('restaurants.view', $restaurant))
            ->assertOk()
            ->assertSee('Log in to start an order')
            ->assertDontSee(route('restaurants.orders.store', $restaurant), false)
            ->assertDontSee('Your orders');
    }

    public function test_members_can_start_an_order_from_the_restaurant_view(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('restaurants.view', $restaurant))
            ->assertOk()
            ->assertSee('Start an order')
            ->assertSee(route('restaurants.orders.store', $restaurant), false)
            ->assertSee('Your orders');
    }

    public function test_order_show_is_public_and_does_not_render_a_verdict(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Public Kitchen']);
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Dumplings']);
        $order = Order::factory()->for($restaurant)->create(['verdict' => 'just_right', 'participant_count' => 3]);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 2]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Public Kitchen')
            ->assertSee('Dumplings')
            ->assertSee('x 2')
            ->assertSee('3 people')
            ->assertSee('Log in to edit or reuse this order')
            ->assertDontSee(route('orders.build', $order), false)
            ->assertDontSee('just_right')
            ->assertDontSee('verdict');
    }

    public function test_members_can_edit_or_reuse_an_order_from_the_order_page(): void
    {
        $order = Order::factory()->for(Restaurant::factory())->create();

        $this->actingAs(User::factory()->create());

        Livewire::test(OrderShow::class, ['order' => $order])
            ->assertSee(route('orders.build', $order), false)
            ->assertSee(route('orders.duplicate', $order), false);
    }

    public function test_an_unknown_party_size_is_not_shown_as_zero_people(): void
    {
        $order = Order::factory()->for(Restaurant::factory())->create(['participant_count' => 0]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('0 people');
    }

    public function test_untitled_orders_are_named_after_when_they_were_ordered(): void
    {
        $order = Order::factory()->for(Restaurant::factory())->create([
            'content' => null,
            'ordered_at' => '2026-11-10 19:30:00', // a Tuesday evening
        ]);

        $this->assertSame('Tuesday night', $order->title);
    }
}
