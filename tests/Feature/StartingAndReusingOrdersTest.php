<?php

namespace Tests\Feature;

use App\Livewire\RestaurantView;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StartingAndReusingOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithADish(Restaurant $restaurant, array $attributes = []): Order
    {
        $menu = $restaurant->menus()->first() ?? Menu::factory()->for($restaurant)->create();
        $order = Order::factory()->for($restaurant)->create($attributes);
        $order->items()->create(['menu_item_id' => MenuItem::factory()->for($menu)->create()->id, 'quantity' => 2]);

        return $order;
    }

    public function test_starting_an_order_uses_the_chosen_head_count(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('restaurants.orders.store', $restaurant), ['participant_count' => 6]);

        $this->assertSame(6, $restaurant->orders()->sole()->participant_count);
    }

    public function test_starting_an_order_rejects_a_silly_head_count(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('restaurants.orders.store', $restaurant), ['participant_count' => 0])
            ->assertSessionHasErrors('participant_count');

        $this->assertSame(0, $restaurant->orders()->count());
    }

    public function test_reusing_an_order_can_change_the_head_count(): void
    {
        $restaurant = Restaurant::factory()->create();
        $original = $this->orderWithADish($restaurant, ['participant_count' => 8]);

        $this->actingAs(User::factory()->create())
            ->post(route('orders.duplicate', $original), ['participant_count' => 3]);

        $copy = Order::whereKeyNot($original->id)->sole();
        $this->assertSame(3, $copy->participant_count);
        $this->assertSame(1, $copy->items()->count());
    }

    public function test_the_start_sheet_offers_your_last_order_here(): void
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create();
        $this->orderWithADish($restaurant, ['user_id' => $user->id, 'content' => 'Older one', 'ordered_at' => now()->subWeek()]);
        $latest = $this->orderWithADish($restaurant, ['user_id' => $user->id, 'content' => 'Latest one', 'participant_count' => 7, 'ordered_at' => now()->subDay()]);
        $this->orderWithADish($restaurant, ['content' => 'Someone else’s', 'ordered_at' => now()]);

        $this->actingAs($user);
        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSee('Your last order here')
            ->assertSee('Latest one · 1 dish')
            ->assertSee(route('orders.duplicate', $latest), false)
            ->assertSee('people: 7', false);
    }

    public function test_the_start_sheet_only_offers_a_blank_order_the_first_time(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create());
        Livewire::test(RestaurantView::class, ['restaurant' => $restaurant])
            ->assertSee('Blank order')
            ->assertDontSee('Your last order here');
    }

    public function test_the_start_sheet_opens_straight_away_when_asked(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->get(route('restaurants.view', ['restaurant' => $restaurant, 'start' => 1]))->assertSee('open: true', false);
        $this->get(route('restaurants.view', $restaurant))->assertSee('open: false', false);
    }

    public function test_every_order_on_the_restaurant_page_can_be_reused(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = $this->orderWithADish($restaurant);

        $this->get(route('restaurants.view', $restaurant))
            ->assertSee(route('orders.reuse', $order), false);

        $this->actingAs(User::factory()->create())
            ->get(route('restaurants.view', $restaurant))
            ->assertSee(route('orders.duplicate', $order), false);
    }

    public function test_the_restaurant_page_has_no_tabs_or_party_size_filter(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('restaurants.view', $restaurant))
            ->assertOk()
            ->assertDontSee('Recent orders')
            ->assertDontSee('Filter: party size');
    }

    public function test_guests_log_in_and_land_back_on_the_start_sheet(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Pickle Jar']);
        $user = User::factory()->create();

        $this->get(route('restaurants.start', $restaurant))->assertRedirect(route('login'));
        $this->get(route('login'))->assertSee('Log in to start an order at Pickle Jar.');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('restaurants.start', $restaurant));
        $this->get(route('restaurants.start', $restaurant))
            ->assertRedirect(route('restaurants.view', ['restaurant' => $restaurant, 'start' => 1]));
    }

    public function test_guests_log_in_and_the_order_they_wanted_is_reused(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Pickle Jar']);
        $order = $this->orderWithADish($restaurant, ['content' => 'Birthday yum cha']);

        $this->get(route('orders.reuse', $order))->assertRedirect(route('login'));
        $this->get(route('login'))->assertSee('Log in to reuse “Birthday yum cha” from Pickle Jar.');

        $this->actingAs(User::factory()->create())
            ->get(route('orders.reuse', $order))
            ->assertRedirect(route('orders.show', ['order' => $order, 'reuse' => 1]));
        $this->get(route('orders.show', ['order' => $order, 'reuse' => 1]))
            ->assertSee('x-init="$el.submit()"', false);
    }

    public function test_the_login_page_keeps_its_usual_message_otherwise(): void
    {
        $this->get(route('login'))->assertSee('Log in to start and edit orders.');
    }
}
