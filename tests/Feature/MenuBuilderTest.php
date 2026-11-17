<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\MenuBuilderCatalog;
use Database\Seeders\KcCafeMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function restaurantWithMenu(): array
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Test Kitchen']);
        $menu = Menu::factory()->for($restaurant)->create(['is_active' => true]);
        $tofu = MenuItem::factory()->for($menu)->create(['name' => 'Mapo Tofu', 'tags' => ['Tofu', 'Spicy'], 'order_index' => 0]);
        $duck = MenuItem::factory()->for($menu)->create([
            'name' => 'Roast Duck', 'tags' => ['Duck'], 'order_index' => 1,
            'unit' => 'whole', 'unit_step' => 0.5, 'unit_start' => 0.5,
        ]);

        return [$restaurant, $tofu, $duck];
    }

    public function test_new_orders_open_in_the_menu_builder(): void
    {
        $restaurant = Restaurant::factory()->create();

        $response = $this->post(route('restaurants.orders.store', $restaurant));

        $order = $restaurant->orders()->first();
        $response->assertRedirect(route('restaurants.orders.build', [$restaurant, $order]));
    }

    public function test_builder_page_boots_with_the_restaurants_menu(): void
    {
        [$restaurant, $tofu] = $this->restaurantWithMenu();
        $order = $restaurant->orders()->create();
        $order->items()->create(['menu_item_id' => $tofu->id, 'quantity' => 2]);

        $response = $this->get(route('restaurants.orders.build', [$restaurant, $order]));

        $response->assertOk()
            ->assertSee('id="menu-builder"', false)
            ->assertSee('Roast Duck')
            ->assertSee('Mapo Tofu');
    }

    public function test_builder_404s_for_an_order_from_another_restaurant(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $other = Restaurant::factory()->create();
        $order = $other->orders()->create();

        $this->get(route('restaurants.orders.build', [$restaurant, $order]))->assertNotFound();
    }

    public function test_sync_replaces_the_orders_lines_and_head_count(): void
    {
        [$restaurant, $tofu, $duck] = $this->restaurantWithMenu();
        $order = $restaurant->orders()->create();
        $old = $order->items()->create(['menu_item_id' => $tofu->id, 'quantity' => 9]);

        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), [
            'people' => 6,
            'items' => [
                ['menuItemId' => $duck->id, 'name' => 'Roast Duck', 'variant' => '', 'qty' => 1.5],
                ['menuItemId' => $tofu->id, 'name' => 'Mapo Tofu', 'variant' => '', 'qty' => 2],
                ['menuItemId' => null, 'name' => 'Spring Rolls', 'variant' => '', 'qty' => 1],
            ],
        ])->assertOk()->assertJson(['saved' => true, 'count' => 3]);

        $order->refresh();
        $this->assertSame(6, $order->participant_count);
        $this->assertSame(['Roast Duck', 'Mapo Tofu', 'Spring Rolls'], $order->items->pluck('name')->all());
        $this->assertSame(1.5, $order->items->first()->quantity);
        $this->assertSame('1.5', $order->items->first()->display_quantity);
        $this->assertDatabaseMissing('order_items', ['id' => $old->id]);
    }

    public function test_the_same_dish_can_be_ordered_in_two_variants(): void
    {
        [$restaurant, $tofu] = $this->restaurantWithMenu();
        $order = $restaurant->orders()->create();

        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), [
            'people' => 4,
            'items' => [
                ['menuItemId' => $tofu->id, 'name' => 'Mapo Tofu', 'variant' => 'Mild', 'qty' => 1],
                ['menuItemId' => $tofu->id, 'name' => 'Mapo Tofu', 'variant' => 'Hot', 'qty' => 1],
            ],
        ])->assertOk();

        $this->assertSame(['Mild', 'Hot'], $order->items()->pluck('variant')->all());
    }

    public function test_sync_rejects_dishes_from_another_restaurants_menu(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $foreign = MenuItem::factory()->create();
        $order = $restaurant->orders()->create();

        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), [
            'people' => 4,
            'items' => [['menuItemId' => $foreign->id, 'name' => $foreign->name, 'variant' => '', 'qty' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_sync_can_clear_the_menu(): void
    {
        [$restaurant, $tofu] = $this->restaurantWithMenu();
        $order = $restaurant->orders()->create();
        $order->items()->create(['menu_item_id' => $tofu->id, 'quantity' => 1]);

        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), ['people' => 2, 'items' => []])
            ->assertOk();

        $this->assertSame(0, $order->items()->count());
    }

    public function test_typed_dishes_are_added_to_the_menu_once(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $order = $restaurant->orders()->create();
        $payload = ['people' => 4, 'items' => [
            ['menuItemId' => null, 'name' => 'Prawn Crackers', 'variant' => '', 'qty' => 1, 'tags' => ['Seafood']],
        ]];

        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), $payload)->assertOk();
        $this->putJson(route('restaurants.orders.build.sync', [$restaurant, $order]), $payload)->assertOk();

        $crackers = MenuItem::where('name', 'Prawn Crackers')->sole();
        $this->assertSame(['Seafood'], $crackers->tags);
        $this->assertSame($restaurant->id, $crackers->menu->restaurant_id);
        $this->assertSame([$crackers->id], $order->items()->pluck('menu_item_id')->all());
    }

    public function test_catalog_ranks_by_order_history(): void
    {
        [$restaurant, $tofu, $duck] = $this->restaurantWithMenu();
        $crackers = $restaurant->findOrCreateMenuItem('Prawn Crackers');
        foreach (range(1, 3) as $i) {
            $past = $restaurant->orders()->create();
            $past->items()->create(['menu_item_id' => $duck->id, 'quantity' => 1]);
            if ($i === 1) {
                $past->items()->create(['menu_item_id' => $crackers->id, 'quantity' => 1]);
            }
        }

        $catalog = MenuBuilderCatalog::for($restaurant);

        $this->assertSame(['Roast Duck', 'Prawn Crackers', 'Mapo Tofu'], array_column($catalog['items'], 'name'));
        $this->assertSame(3, $catalog['items'][0]['uses']);
        $this->assertSame(['name' => 'whole', 'step' => 0.5, 'start' => 0.5], $catalog['items'][0]['unit']);
        $this->assertSame(['Tofu', 'Spicy', 'Duck'], $catalog['tags']);
    }

    public function test_kc_cafe_seeder_loads_the_prototype_menu(): void
    {
        $this->seed(KcCafeMenuSeeder::class);
        $this->seed(KcCafeMenuSeeder::class); // idempotent

        $restaurant = Restaurant::where('name', 'K.C Cafe')->sole();
        $catalog = MenuBuilderCatalog::for($restaurant);

        $this->assertCount(24, $catalog['items']);
        $this->assertSame('Duck Meat Fried Rice', $catalog['items'][0]['name']);
        $ho = collect($catalog['items'])->firstWhere('name', 'Ho Fun');
        $this->assertSame(['Beef Brisket', 'Beef'], $ho['variants']);
    }
}
