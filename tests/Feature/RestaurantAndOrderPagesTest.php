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
            ->assertSee(route('restaurants.start', $restaurant), false)
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
            ->assertSee('Blank order');
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
            ->assertSee('Log in to reuse this order')
            ->assertSee(route('orders.reuse', $order), false)
            ->assertDontSee(route('orders.build', $order), false)
            ->assertDontSee('just_right')
            ->assertDontSee('verdict');
    }

    public function test_owners_can_edit_or_reuse_an_order_from_the_order_page(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for(Restaurant::factory())->create(['user_id' => $owner->id]);

        $this->actingAs($owner);

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

    public function test_orders_with_no_date_are_listed_after_dated_ones(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menuItem = MenuItem::factory()->for(Menu::factory()->for($restaurant))->create();

        $undated = Order::factory()->for($restaurant)->create(['content' => 'From the old notes', 'ordered_at' => null]);
        $dated = Order::factory()->for($restaurant)->create(['content' => 'Last Friday', 'ordered_at' => now()->subYear()]);
        foreach ([$undated, $dated] as $order) {
            $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 1]);
        }

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSeeInOrder(['Last Friday', 'From the old notes'])
            ->assertSee('Date not noted');
    }

    public function test_receipts_total_the_metres_of_pizza_ordered(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $pizza = MenuItem::factory()->for($menu)->create(['name' => 'Amore', 'unit' => MenuItem::UNIT_METRES]);
        $fries = MenuItem::factory()->for($menu)->create(['name' => 'Fries']);
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 3, 'ordered_at' => now()]);
        $order->items()->createMany([
            ['menu_item_id' => $pizza->id, 'quantity' => 0.75],
            ['menu_item_id' => $fries->id, 'quantity' => 2],
        ]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('0.75m pizza')
            ->assertSee('0.25m each')
            ->assertSee('x 2');
    }

    public function test_receipts_leave_out_metres_when_nothing_is_sold_by_the_metre(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menuItem = MenuItem::factory()->for(Menu::factory()->for($restaurant))->create();
        $order = Order::factory()->for($restaurant)->create();
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 2]);

        $this->get(route('orders.show', $order))->assertOk()->assertDontSee('m pizza');
    }
}
