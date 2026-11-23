<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Pickle Jar with its menu and the orders from the notes kept
 * over the years, oldest first.
 *
 * Pizzas are sold by the metre: a "1/2m" pizza is 0.5, and a half-metre
 * split between two flavours ("Margherita / Isola") is two 0.25 lines.
 *
 * Safe to run again: it replaces the orders it seeded before (orders with no
 * owner) and leaves orders people made in the app alone.
 */
class PickleJarSeeder extends Seeder
{
    private const PIZZA_UNIT = [MenuItem::UNIT_METRES, 0.25, 0.5];

    /** Stands in for pizza when the notes only recorded how much was ordered. */
    public const UNRECORDED_PIZZA = 'Pizza (flavours not noted)';

    /**
     * Dishes on the menu: [name, tags, variants, [unit, step, first quantity]].
     *
     * @var list<array{0: string, 1: list<string>, 2?: list<string>|null, 3?: array{0: string, 1: float, 2: float}|null}>
     */
    private const DISHES = [
        ['Amore', ['Pizza'], null, self::PIZZA_UNIT],
        ['Speziato', ['Pizza', 'Spicy'], null, self::PIZZA_UNIT],
        ['Margherita', ['Pizza', 'Vegetarian'], null, self::PIZZA_UNIT],
        ['Homewood', ['Pizza'], null, self::PIZZA_UNIT],
        ['Mertilla di Pollo', ['Pizza', 'Chicken'], null, self::PIZZA_UNIT],
        ['Ponsonby Road', ['Pizza'], null, self::PIZZA_UNIT],
        ['Tricolore', ['Pizza'], null, self::PIZZA_UNIT],
        ['Messines', ['Pizza'], null, self::PIZZA_UNIT],
        ['Menzo Bernese', ['Pizza'], null, self::PIZZA_UNIT],
        ['Selvaggio', ['Pizza'], null, self::PIZZA_UNIT],
        ['Isola', ['Pizza'], null, self::PIZZA_UNIT],
        ['Gipps', ['Pizza'], null, self::PIZZA_UNIT],
        ['Birdwood', ['Pizza'], null, self::PIZZA_UNIT],
        ['Hatton', ['Pizza'], null, self::PIZZA_UNIT],
        ['Beauchamp', ['Pizza'], null, self::PIZZA_UNIT],
        ['Monaghan', ['Pizza'], null, self::PIZZA_UNIT],
        ['Campbell', ['Pizza'], null, self::PIZZA_UNIT],
        ['Standen', ['Pizza'], null, self::PIZZA_UNIT],
        ['Cargill', ['Pizza'], null, self::PIZZA_UNIT],
        ['Richmond', ['Pizza'], null, self::PIZZA_UNIT],
        ['Lancaster', ['Pizza'], null, self::PIZZA_UNIT],
        ['Arrosto', ['Pizza'], null, self::PIZZA_UNIT],
        ['Chicken', ['Pizza', 'Chicken'], null, self::PIZZA_UNIT],
        ['Lamb', ['Pizza'], null, self::PIZZA_UNIT],
        [self::UNRECORDED_PIZZA, ['Pizza'], null, self::PIZZA_UNIT],
        ['Fries', ['Sides']],
        ['Korean Wings', ['Sides', 'Chicken'], ['Half', 'Full']],
        ['Spicy Chicken Burger', ['Chicken']],
        ['Golden Curry', []],
    ];

    /**
     * The orders from the notes, oldest first. Each item is
     * [dish, quantity, variant, notes]; pizza quantities are metres. The
     * order's note becomes its title.
     *
     * Names (attendees, attendee_notes) are only shown to signed-in users.
     *
     * @var list<array{people: int, verdict: string, notes?: string, attendees?: list<string>, attendee_notes?: string, items: list<array{0: string, 1: float, 2?: string|null, 3?: string|null}>}>
     */
    private const ORDERS = [
        [
            'people' => 8, 'verdict' => 'just_right', 'notes' => 'About perfect.',
            'items' => [['Arrosto', 1], ['Chicken', 0.5], ['Lamb', 0.5]],
        ],
        [
            'people' => 10, 'verdict' => 'too_much', 'notes' => 'Too much. For 10 people should get 2.5m.',
            'items' => [[self::UNRECORDED_PIZZA, 3]],
        ],
        [
            'people' => 5, 'verdict' => 'too_much', 'notes' => 'A little bit too much.',
            'attendees' => ['Papa', 'Rodger', 'James', 'Michael', 'Jeff'],
            'items' => [[self::UNRECORDED_PIZZA, 1.5]],
        ],
        [
            'people' => 9, 'verdict' => 'too_much', 'notes' => '0.5m left over.',
            'attendees' => ['Papa', 'Rodger', 'Patricia', 'Janet', 'Belinda', 'Michael', 'Awhina', 'Jeff', 'James'],
            'attendee_notes' => 'James not that hungry.',
            'items' => [[self::UNRECORDED_PIZZA, 2], ['Fries', 2]],
        ],
        [
            'people' => 10, 'verdict' => 'just_right', 'notes' => '4 slices left.',
            'attendees' => ['Papa', 'Rodger', 'Patricia', 'Jacqui', 'Belinda', 'Michael', 'Awhina', 'Jeff', 'Atawhai'],
            'items' => [[self::UNRECORDED_PIZZA, 2], ['Fries', 2]],
        ],
        [
            'people' => 7, 'verdict' => 'just_right', 'notes' => 'Perfect.',
            'attendees' => ['Papa', 'Rodger', 'Kevin', 'Michael', 'Jeff', 'Bel', 'Sage'],
            'items' => [[self::UNRECORDED_PIZZA, 2]],
        ],
        [
            'people' => 11, 'verdict' => 'too_much', 'notes' => 'One 0.5m pizza too many, but we got 2 x chips.',
            'attendee_notes' => 'Everyone except Jacqui.',
            'items' => [
                ['Mertilla di Pollo', 0.5],
                ['Speziato', 0.5],
                ['Selvaggio', 0.5],
                ['Amore', 0.5],
                ['Margherita', 0.5, null, 'Noted as "Margherita Amore speziato"'],
                ['Fries', 2],
            ],
        ],
        [
            'people' => 6, 'verdict' => 'too_much', 'notes' => 'A little bit too much.',
            'attendees' => ['Papa', 'Rodger', 'James', 'Michael', 'Jeff', 'Janet'],
            'items' => [['Amore', 0.5], ['Speziato', 0.5], ['Menzo Bernese', 0.5]],
        ],
        [
            'people' => 6, 'verdict' => 'not_enough', 'notes' => 'Not enough. Controversial.',
            'attendees' => ['Michael', 'Jacqui', 'Papa', 'Jeff', 'Janet', 'Kevin'],
            'attendee_notes' => 'Dad should have left a piece for…',
            'items' => [[self::UNRECORDED_PIZZA, 1]],
        ],
        [
            'people' => 4, 'verdict' => 'too_much', 'notes' => 'Too much, but we wanted leftovers.',
            'attendees' => ['Michael', 'Jacqui', 'Papa', 'Jamie'],
            'items' => [['Speziato', 0.5], ['Amore', 0.5], ['Tricolore', 0.25], ['Mertilla di Pollo', 0.25]],
        ],
        [
            'people' => 5, 'verdict' => 'too_much', 'notes' => '0.5m left over.',
            'attendees' => ['Michael', 'Kevin', 'Papa', 'Bel', 'Jan'],
            'items' => [[self::UNRECORDED_PIZZA, 1.5]],
        ],
        [
            'people' => 6, 'verdict' => 'pending',
            'attendees' => ['Michael', 'Janet', 'Jacqui', 'Awhina', 'Atawhai', 'Kevin'],
            'items' => [['Amore', 0.5], ['Speziato', 0.5], ['Mertilla di Pollo', 0.25], ['Tricolore', 0.25], ['Fries', 1]],
        ],
        [
            'people' => 9, 'verdict' => 'pending',
            'items' => [
                ['Margherita', 0.25], ['Mertilla di Pollo', 0.25],
                ['Amore', 0.5],
                ['Speziato', 0.5],
                ['Tricolore', 0.25], ['Menzo Bernese', 0.25],
            ],
        ],
        [
            'people' => 7, 'verdict' => 'pending',
            'items' => [
                ['Margherita', 0.25], ['Mertilla di Pollo', 0.25],
                ['Speziato', 0.5],
                ['Amore', 0.5],
                ['Selvaggio', 0.25, null, 'Size not noted'], ['Isola', 0.25, null, 'Size not noted'],
            ],
        ],
        [
            'people' => 12, 'verdict' => 'pending',
            'items' => [
                ['Amore', 1],
                ['Speziato', 0.5],
                ['Margherita', 0.25], ['Isola', 0.25],
                ['Ponsonby Road', 0.25], ['Messines', 0.25],
                ['Homewood', 0.5],
            ],
        ],
        [
            'people' => 14, 'verdict' => 'pending',
            'attendees' => ['Belinda', 'Sage', 'Michael', 'Janet', 'Rodger', 'Kevin', 'Annaleise', 'Jamie', 'Atawhai', 'Bede', 'Patricia', 'Awhina', 'James', 'Jeff'],
            'items' => [
                ['Amore', 0.5],
                ['Homewood', 1],
                ['Ponsonby Road', 0.5],
                ['Speziato', 1],
                ['Margherita', 0.25], ['Messines', 0.25],
            ],
        ],
        [
            'people' => 9, 'verdict' => 'pending',
            'items' => [['Homewood', 1], ['Amore', 0.5], ['Ponsonby Road', 0.5]],
        ],
        [
            'people' => 0, 'verdict' => 'pending',
            'items' => [
                ['Homewood', 0.5],
                ['Speziato', 0.5],
                ['Margherita', 0.25], ['Mertilla di Pollo', 0.25, null, 'No olives'],
                ['Amore', 0.5],
            ],
        ],
        [
            'people' => 9, 'verdict' => 'pending', 'notes' => 'Marked "W" in the notes.',
            'items' => [
                ['Homewood', 0.5],
                ['Speziato', 0.5],
                ['Mertilla di Pollo', 0.5],
                ['Amore', 0.25], ['Messines', 0.25],
            ],
        ],
        [
            'people' => 0, 'verdict' => 'pending', 'notes' => 'Marked "L" in the notes.',
            'items' => [
                ['Birdwood', 0.5],
                ['Monaghan', 0.25], ['Campbell', 0.25],
                ['Hatton', 0.25], ['Beauchamp', 0.25],
                ['Gipps', 0.5],
            ],
        ],
        [
            'people' => 12, 'verdict' => 'just_right', 'notes' => 'On the lighter side, but was enough.',
            'items' => [
                ['Birdwood', 0.5],
                ['Gipps', 0.5],
                ['Beauchamp', 0.5],
                ['Margherita', 0.5],
                ['Ponsonby Road', 0.5],
                ['Korean Wings', 1, 'Full'],
                ['Fries', 2],
                ['Spicy Chicken Burger', 1],
            ],
        ],
        [
            'people' => 0, 'verdict' => 'just_right', 'notes' => 'Good amount.',
            'items' => [
                ['Margherita', 0.25], ['Standen', 0.25], ['Hatton', 0.25], ['Gipps', 0.25],
                ['Cargill', 0.25], ['Richmond', 0.5], ['Lancaster', 0.25],
            ],
        ],
        [
            'people' => 0, 'verdict' => 'pending',
            'items' => [['Golden Curry', 1], ['Margherita', 0.5]],
        ],
        [
            'people' => 8, 'verdict' => 'too_much', 'notes' => 'Too much, maybe should have had…',
            'items' => [[self::UNRECORDED_PIZZA, 2]],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $restaurant = Restaurant::query()->where('name', 'like', '%Pickle Jar%')->orderBy('id')->first()
                ?? Restaurant::create(['name' => 'The Pickle Jar', 'google_place_id' => 'seed:pickle-jar']);

            $restaurant->orders()->whereNull('user_id')->get()->each->delete();

            $menu = Menu::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => 'Main Menu'],
                ['menu_type' => 'manual', 'is_active' => true],
            );

            $menuItemIds = $this->seedMenu($menu);

            foreach (self::ORDERS as $orderData) {
                $order = $restaurant->orders()->create([
                    'user_id' => null,
                    'content' => $orderData['notes'] ?? 'Pizza night',
                    'participant_count' => $orderData['people'],
                    'verdict' => $orderData['verdict'],
                    'attendees' => $orderData['attendees'] ?? null,
                    'attendee_notes' => $orderData['attendee_notes'] ?? null,
                    'ordered_at' => null,
                ]);

                foreach ($orderData['items'] as $position => $item) {
                    [$dish, $quantity, $variant, $itemNotes] = $item + [2 => null, 3 => null];

                    $order->items()->create([
                        'menu_item_id' => $menuItemIds[$dish],
                        'quantity' => $quantity,
                        'variant' => $variant,
                        'notes' => $itemNotes,
                        'position' => $position,
                    ]);
                }
            }
        });
    }

    /**
     * Put the dishes on the menu, and take off dishes from earlier runs of this
     * seeder that no order uses any more.
     *
     * @return array<string, int> menu item ids keyed by dish name
     */
    private function seedMenu(Menu $menu): array
    {
        $ids = [];

        foreach (self::DISHES as $index => $dish) {
            [$name, $tags, $variants, $unit] = $dish + [2 => null, 3 => null];

            $ids[$name] = $menu->menuItems()->updateOrCreate(['name' => $name], [
                'tags' => $tags,
                'variants' => $variants,
                'unit' => $unit[0] ?? null,
                'unit_step' => $unit[1] ?? null,
                'unit_start' => $unit[2] ?? null,
                'order_index' => $index,
                'is_available' => true,
            ])->id;
        }

        $menu->menuItems()
            ->whereNotIn('id', $ids)
            ->whereDoesntHave('orderItems')
            ->delete();

        return $ids;
    }
}
