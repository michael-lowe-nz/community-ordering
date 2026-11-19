<?php

namespace Tests\Feature;

use App\Livewire\OrderShow;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Order, 1: MenuItem, 2: User}
     */
    private function ownedOrderWithADish(): array
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->for($restaurant)->create();
        $dish = MenuItem::factory()->for($menu)->create(['name' => 'Har Gow']);
        $owner = User::factory()->create();
        $order = Order::factory()->for($restaurant)->create(['user_id' => $owner->id, 'participant_count' => 4]);
        $order->items()->create(['menu_item_id' => $dish->id, 'quantity' => 3]);

        return [$order, $dish, $owner];
    }

    public function test_only_the_owner_can_open_or_save_an_order_in_the_builder(): void
    {
        [$order, $dish, $owner] = $this->ownedOrderWithADish();
        $someoneElse = User::factory()->create();
        $payload = ['people' => 9, 'items' => []];

        $this->actingAs($someoneElse)->get(route('orders.build', $order))->assertForbidden();
        $this->actingAs($someoneElse)->putJson(route('orders.build.sync', $order), $payload)->assertForbidden();
        $this->actingAs($someoneElse)->deleteJson(route('orders.discard', $order))->assertForbidden();

        $this->assertSame(4, $order->fresh()->participant_count);
        $this->assertSame(1, $order->items()->count());

        $this->actingAs($owner)->get(route('orders.build', $order))->assertOk();
    }

    public function test_orders_with_no_owner_cannot_be_edited(): void
    {
        $order = Order::factory()->for(Restaurant::factory())->create(['user_id' => null]);

        $this->actingAs(User::factory()->create())->get(route('orders.build', $order))->assertForbidden();
    }

    public function test_the_order_page_offers_edit_to_the_owner_and_reuse_to_everyone_else(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();

        $this->actingAs($owner);
        Livewire::test(OrderShow::class, ['order' => $order])
            ->assertSee(route('orders.build', $order), false)
            ->assertSee(route('orders.duplicate', $order), false)
            ->assertSee('Notes');

        $this->actingAs(User::factory()->create());
        Livewire::test(OrderShow::class, ['order' => $order])
            ->assertDontSee(route('orders.build', $order), false)
            ->assertSee(route('orders.duplicate', $order), false)
            ->assertSee('Reuse this order')
            ->assertDontSee('Save notes');
    }

    public function test_the_owner_can_save_notes(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();

        $this->actingAs($owner);
        Livewire::test(OrderShow::class, ['order' => $order])
            ->set('notes', '  One vegetarian  ')
            ->call('saveNotes')
            ->assertHasNoErrors()
            ->assertDispatched('notes-saved');

        $this->assertSame('One vegetarian', $order->fresh()->notes);
    }

    public function test_clearing_notes_stores_nothing(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();
        $order->update(['notes' => 'Old note']);

        $this->actingAs($owner);
        Livewire::test(OrderShow::class, ['order' => $order])
            ->set('notes', '   ')
            ->call('saveNotes');

        $this->assertNull($order->fresh()->notes);
    }

    public function test_notes_are_limited_in_length(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();

        $this->actingAs($owner);
        Livewire::test(OrderShow::class, ['order' => $order])
            ->set('notes', str_repeat('a', 2001))
            ->call('saveNotes')
            ->assertHasErrors(['notes' => 'max']);

        $this->assertNull($order->fresh()->notes);
    }

    public function test_other_people_cannot_save_notes(): void
    {
        [$order] = $this->ownedOrderWithADish();

        $this->actingAs(User::factory()->create());
        Livewire::test(OrderShow::class, ['order' => $order])
            ->set('notes', 'Sneaky')
            ->call('saveNotes')
            ->assertForbidden();

        $this->assertNull($order->fresh()->notes);
    }

    public function test_sync_saves_the_order_title(): void
    {
        [$order, $dish, $owner] = $this->ownedOrderWithADish();
        $items = [['menuItemId' => $dish->id, 'name' => 'Har Gow', 'variant' => '', 'qty' => 3]];

        $this->actingAs($owner)->putJson(route('orders.build.sync', $order), ['title' => '  Friday lunch ', 'people' => 4, 'items' => $items])->assertOk();
        $this->assertSame('Friday lunch', $order->fresh()->title);

        // leaving the title out of a save keeps it
        $this->actingAs($owner)->putJson(route('orders.build.sync', $order), ['people' => 4, 'items' => $items])->assertOk();
        $this->assertSame('Friday lunch', $order->fresh()->title);

        // a blank title goes back to the day and time of day
        $this->actingAs($owner)->putJson(route('orders.build.sync', $order), ['title' => '', 'people' => 4, 'items' => $items])->assertOk();
        $this->assertNull($order->fresh()->content);
    }

    public function test_sync_rejects_an_overly_long_title(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();

        $this->actingAs($owner)->putJson(route('orders.build.sync', $order), ['title' => str_repeat('a', 101), 'people' => 4, 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_discarding_removes_an_empty_order_but_keeps_one_with_dishes(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();
        $empty = Order::factory()->for($order->restaurant)->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->deleteJson(route('orders.discard', $empty))->assertNoContent();
        $this->actingAs($owner)->deleteJson(route('orders.discard', $order))->assertNoContent();

        $this->assertModelMissing($empty);
        $this->assertModelExists($order);
    }

    public function test_the_order_can_be_copied_as_text(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();
        $order->update(['content' => 'Friday lunch', 'notes' => 'No peanuts']);
        $order->items()->create(['menu_item_id' => MenuItem::factory()->for($order->restaurant->menus()->first())->create(['name' => 'Ho Fun'])->id, 'variant' => 'Beef', 'quantity' => 1.5]);
        $order->load('restaurant', 'items.menuItem');

        $this->assertSame(
            "Friday lunch · {$order->restaurant->name} · 4 people\n\nHar Gow x 3\nHo Fun (Beef) x 1.5\n\nNo peanuts",
            $order->toPlainText(),
        );

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Copy as text')
            ->assertSee('Share link');
    }

    public function test_coming_back_from_the_builder_says_the_order_was_saved(): void
    {
        [$order, , $owner] = $this->ownedOrderWithADish();

        $this->actingAs($owner)->get(route('orders.show', ['order' => $order, 'saved' => 1]))
            ->assertOk()
            ->assertSee('Saved. Copy it to send to the group');

        $this->actingAs($owner)->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Saved. Copy it to send to the group');
    }
}
