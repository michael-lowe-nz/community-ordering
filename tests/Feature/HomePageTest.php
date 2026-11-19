<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_includes_search_copy_and_search_input(): void
    {
        Restaurant::factory()->count(6)->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('find your favourite spots')
            ->assertSee('Search for a spot');
    }

    public function test_home_page_lists_your_orders_including_empty_ones(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['name' => 'Pickle Jar']);
        $empty = Order::factory()->for($restaurant)->create(['user_id' => $user->id, 'content' => 'Half-started order']);
        $finished = Order::factory()->for($restaurant)->create(['user_id' => $user->id, 'content' => 'Friday lunch']);
        $finished->items()->create(['menu_item_id' => $restaurant->findOrCreateMenuItem('Har Gow')->id, 'quantity' => 3]);
        Order::factory()->for($restaurant)->create(['user_id' => User::factory()->create()->id, 'content' => 'Someone else’s order']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('id="your-orders"', false)
            ->assertSee('Half-started order')
            ->assertSee(route('orders.build', $empty), false)
            ->assertSee('Friday lunch')
            ->assertSee(route('orders.show', $finished), false)
            ->assertDontSee('Someone else’s order');
    }

    public function test_guests_do_not_see_a_your_orders_section(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="your-orders"', false);
    }

    public function test_home_search_filters_as_you_type_and_can_show_every_spot(): void
    {
        Restaurant::factory()->count(8)->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('>Find<', false)
            ->assertSee('Show all ${restaurants.length} spots', false);
    }
}
