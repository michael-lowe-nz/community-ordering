<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PickleJarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurant = Restaurant::query()
            ->where('name', 'like', '%Pickle Jar%')
            ->first();

        if (! $restaurant) {
            $restaurant = Restaurant::firstOrCreate(
                ['name' => 'The Pickle Jar'],
                ['google_place_id' => 'pickle-jar-seeder']
            );
        }

        $restaurant->orders()->each(function ($order) {
            $order->menuItems()->detach();
            $order->delete();
        });

        $restaurant->menus()->each(function (Menu $menu) {
            $menu->menuItems()->delete();
            $menu->delete();
        });

        $menu = $restaurant->menus()->create([
            'name' => 'Main Menu',
            'menu_type' => 'manual',
            'is_active' => true,
        ]);

        $orders = [
            [
                'notes' => 'About perfect',
                'items' => [
                    ['name' => 'Arrosto', 'quantity' => 2],
                    ['name' => 'Chicken', 'quantity' => 1],
                    ['name' => 'Lamb', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Too much - for 10 people should get 2.5m',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 3],
                ],
            ],
            [
                'notes' => 'Papa, Rodger, James, Michael, Jeff - little bit too much',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '9 people: Papa, Rodger, Patricia, Janet, Belinda, Michael, Awhina, Jeff - 1m left over',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                    ['name' => 'Chips', 'quantity' => 2],
                ],
            ],
            [
                'notes' => '10 people: Papa, Rodger, Patricia, Jacqui, Belinda, Michael, Awhina, Jeff, Atawhai - 4 slices left',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                    ['name' => 'Chips', 'quantity' => 2],
                ],
            ],
            [
                'notes' => '7 people: Papa, Rodger, Kevin, Michael, Jeff, Bel, Sage - Perfect',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                ],
            ],
            [
                'notes' => 'Mertilla Di Pollo, Speziato, Selvaggio, Amore, Margherita Amore Speziato',
                'items' => [
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Selvaggio', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Margherita Amore Speziato', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '11 people except Jacqui - pizza too many',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                    ['name' => 'Chips', 'quantity' => 2],
                ],
            ],
            [
                'notes' => 'Papa, Rodger, James, Michael, Jeff, Janet - little bit too much',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Menzo Bernese', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Michael, Jacqui, Papa, Jeff, Janet, Kevin - not enough (controversial)',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Michael, Jacqui, Papa, Jamie - too much but wanted leftovers',
                'items' => [
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Tricolore', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Michael, Kevin, Papa, Bel, Jan - 1m left over',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 15],
                ],
            ],
            [
                'notes' => 'Michael, Janet, Jacqui, Awhina, Atawhai, Kevin - 6 people',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Chips', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Menzo Bernese', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '7 people order',
                'items' => [
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Selvaggio', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '12 people order',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Ponsonby', 'quantity' => 1],
                    ['name' => 'Homewood', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '14 people: Belinda, Sage, Michael, Janet, Rodger, Kevin, Annaleise, Jamie, Atawhai, Bede, Patricia, Awhina, James, Jeff',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Ponsonby Road', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Ponsonby', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Another order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                ],
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Amore Messines', 'quantity' => 1],
                ],
            ],
            [
                'notes' => 'Final orders',
                'items' => [
                    ['name' => 'Birdwood', 'quantity' => 1],
                    ['name' => 'Monaghan', 'quantity' => 1],
                    ['name' => 'Hatton', 'quantity' => 1],
                    ['name' => 'Beauchamp', 'quantity' => 1],
                    ['name' => 'Gipps', 'quantity' => 1],
                ],
            ],
        ];

        $menuItemNames = collect($orders)
            ->flatMap(fn (array $order) => array_map(fn (array $item) => $item['name'], $order['items']))
            ->unique(fn (string $name) => Str::lower(trim($name)))
            ->values()
            ->all();

        foreach ($menuItemNames as $index => $itemName) {
            $normalizedName = trim($itemName);

            $menuItem = $menu->menuItems()
                ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedName)])
                ->first();

            if (! $menuItem) {
                $menuItem = $menu->menuItems()->create([
                    'name' => $normalizedName,
                    'description' => null,
                    'price' => null,
                    'section' => null,
                    'order_index' => $index + 1,
                    'is_available' => true,
                ]);
            }
        }

        foreach ($orders as $orderData) {
            $order = Order::query()->create([
                'restaurant_id' => $restaurant->id,
                'user_id' => null,
                'content' => $orderData['notes'],
                'participant_count' => 0,
            ]);

            foreach ($orderData['items'] as $itemData) {
                $menuItem = $menu->menuItems()
                    ->whereRaw('LOWER(name) = ?', [Str::lower(trim($itemData['name']))])
                    ->first();

                if (! $menuItem) {
                    $menuItem = MenuItem::query()->create([
                        'menu_id' => $menu->id,
                        'name' => trim($itemData['name']),
                        'description' => null,
                        'price' => null,
                        'section' => null,
                        'order_index' => $menu->menuItems()->count() + 1,
                        'is_available' => true,
                    ]);
                }

                $order->menuItems()->syncWithoutDetaching([
                    $menuItem->id => [
                        'quantity' => (int) $itemData['quantity'],
                        'price' => $menuItem->price,
                        'special_requests' => null,
                    ],
                ]);

                $order->items()->create([
                    'name' => $menuItem->name,
                    'quantity' => (int) $itemData['quantity'],
                    'price' => $menuItem->price,
                    'notes' => null,
                ]);
            }
        }
    }
}
