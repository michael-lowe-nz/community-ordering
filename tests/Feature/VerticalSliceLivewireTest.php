<?php

namespace Tests\Feature;

use App\Livewire\OrderCreate;
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

class VerticalSliceLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_view_renders_guidance_and_recent_orders(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'The Noodle House']);
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Mapo tofu']);
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 1]);

        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSee('The Noodle House')
            ->assertSee('Mapo tofu')
            ->assertSee('2 people');
    }

    public function test_party_size_filter_is_independent_from_guidance_party_size(): void
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
            ->set('partySize', 12)
            ->set('partySizeFilter', 2)
            ->assertSee('Small party dish')
            ->assertDontSee('Large party dish')
            ->assertSet('partySize', 12);
    }

    public function test_order_create_requires_authentication(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->get(route('restaurants.orders.create.livewire', $restaurant))
            ->assertRedirect(route('login'));
    }

    public function test_order_create_saves_participant_count_notes_and_both_item_sources(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Fried rice']);
        $chefSpecial = MenuItem::factory()->for($menu)->create(['name' => 'Chef special']);
        $order = Order::factory()->for($restaurant)->for($user)->create(['participant_count' => 4]);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 2]);
        $order->items()->create(['menu_item_id' => $chefSpecial->id, 'quantity' => 1]);

        $this->actingAs($user);

        Livewire::test(OrderCreate::class, ['restaurant' => $restaurant, 'order' => $order])
            ->set('participantCount', 5)
            ->set('notes', 'Less spicy')
            ->call('save')
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'participant_count' => 5,
            'notes' => 'Less spicy',
        ]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_item_id' => $menuItem->id, 'quantity' => 2]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_item_id' => $chefSpecial->id, 'quantity' => 1]);
    }

    public function test_order_show_is_public_and_does_not_render_a_verdict(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Public Kitchen']);
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Dumplings']);
        $order = Order::factory()->for($restaurant)->create(['verdict' => 'just_right', 'participant_count' => 3]);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 2]);

        Livewire::test(OrderShow::class, ['order' => $order])
            ->assertSee('Public Kitchen')
            ->assertSee('Dumplings')
            ->assertDontSee('just_right')
            ->assertDontSee('verdict');
    }

    public function test_authenticated_user_can_use_an_order_as_a_starting_point(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['name' => 'Dumplings']);
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 3]);
        $order->items()->create(['menu_item_id' => $menuItem->id, 'quantity' => 2]);

        $this->actingAs($user);

        Livewire::test(OrderShow::class, ['order' => $order])
            ->call('useAsStartingPoint')
            ->assertRedirectContains(route('restaurants.orders.create.livewire', $restaurant));

        $draft = Order::query()->where('user_id', $user->id)->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertDatabaseHas('order_items', ['order_id' => $draft->id, 'menu_item_id' => $menuItem->id, 'quantity' => 2]);
    }
}
