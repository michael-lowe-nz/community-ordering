<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderGuidanceCalculator;
use App\Services\SimpleAverageOrderGuidanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderGuidanceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_guidance_when_the_restaurant_has_no_orders(): void
    {
        $restaurant = Restaurant::factory()->create();

        $guidance = app(OrderGuidanceCalculator::class)->calculate($restaurant, 4);

        $this->assertSame(4, $guidance->partySize);
        $this->assertSame([], $guidance->categoryBreakdown);
        $this->assertSame(0, $guidance->totalDishes);
    }

    public function test_it_calculates_a_single_order(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $menu = Menu::factory()->for($restaurant)->create();
        $main = MenuItem::factory()->for($menu)->inSection('Mains')->create();
        $dessert = MenuItem::factory()->for($menu)->inSection('Desserts')->create();

        $order->menuItems()->attach($main, ['quantity' => 3]);
        $order->menuItems()->attach($dessert, ['quantity' => 1]);

        $guidance = app(OrderGuidanceCalculator::class)->calculate($restaurant, 4);

        $this->assertSame([
            ['category' => 'Desserts', 'count' => 2],
            ['category' => 'Mains', 'count' => 6],
        ], $guidance->categoryBreakdown);
        $this->assertSame(8, $guidance->totalDishes);
    }

    public function test_it_averages_per_order_ratios_before_scaling(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $main = MenuItem::factory()->for($menu)->inSection('Mains')->create();

        $firstOrder = Order::factory()->for($restaurant)->create(['participant_count' => 1]);
        $firstOrder->menuItems()->attach($main, ['quantity' => 2]);
        $secondOrder = Order::factory()->for($restaurant)->create(['participant_count' => 4]);
        $secondOrder->menuItems()->attach($main, ['quantity' => 4]);

        $guidance = app(OrderGuidanceCalculator::class)->calculate($restaurant, 2);

        $this->assertSame([['category' => 'Mains', 'count' => 3]], $guidance->categoryBreakdown);
        $this->assertSame(3, $guidance->totalDishes);
    }

    public function test_missing_categories_contribute_zero_to_the_average(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $main = MenuItem::factory()->for($menu)->inSection('Mains')->create();
        $dessert = MenuItem::factory()->for($menu)->inSection('Desserts')->create();

        $firstOrder = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $firstOrder->menuItems()->attach($main, ['quantity' => 2]);
        $secondOrder = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $secondOrder->menuItems()->attach($dessert, ['quantity' => 2]);

        $guidance = app(OrderGuidanceCalculator::class)->calculate($restaurant, 2);

        $this->assertSame([
            ['category' => 'Desserts', 'count' => 1],
            ['category' => 'Mains', 'count' => 1],
        ], $guidance->categoryBreakdown);
    }

    public function test_it_places_ad_hoc_items_in_other(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 2]);
        $order->items()->create(['name' => 'Chef special', 'quantity' => 3]);

        $guidance = app(SimpleAverageOrderGuidanceCalculator::class)->calculate($restaurant, 2);

        $this->assertSame([['category' => 'Other', 'count' => 3]], $guidance->categoryBreakdown);
    }

    public function test_it_rejects_a_party_size_below_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(OrderGuidanceCalculator::class)->calculate(Restaurant::factory()->create(), 0);
    }

    public function test_it_excludes_categories_that_round_to_zero(): void
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $main = MenuItem::factory()->for($menu)->inSection('Mains')->create();
        $order = Order::factory()->for($restaurant)->create(['participant_count' => 10]);
        $order->menuItems()->attach($main, ['quantity' => 1]);

        $guidance = app(OrderGuidanceCalculator::class)->calculate($restaurant, 1);

        $this->assertSame([], $guidance->categoryBreakdown);
        $this->assertSame(0, $guidance->totalDishes);
    }
}
