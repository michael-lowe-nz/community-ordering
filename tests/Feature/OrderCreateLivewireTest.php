<?php

namespace Tests\Feature;

use App\Livewire\Order\Create as OrderCreate;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCreateLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_a_dynamic_default_title_for_the_current_day_and_time_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-03-15 13:00:00'));

        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create();
        $order = Order::make([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'content' => null,
        ]);

        $this->assertSame('Saturday lunch', $order->title);

        Livewire::test(OrderCreate::class, ['restaurant' => $restaurant, 'order' => $order])
            ->assertSet('orderTitle', 'Saturday lunch');

        Carbon::setTestNow();
    }

    public function test_it_searches_existing_menu_items_and_creates_new_items_when_missing(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Dinner Menu',
        ]);

        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Classic Burger',
            'price' => 18.50,
            'is_available' => true,
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'content' => null,
        ]);

        Livewire::test(OrderCreate::class, ['restaurant' => $restaurant, 'order' => $order])
            ->set('query', 'Classic')
            ->assertSet('suggestions', ['Classic Burger'])
            ->set('query', 'House Pasta')
            ->set('quantity', 2)
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'name' => 'House Pasta',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'name' => 'House Pasta',
            'quantity' => 2,
        ]);
    }
}
