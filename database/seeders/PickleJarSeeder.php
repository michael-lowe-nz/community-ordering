<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class PickleJarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create Pickle Jar restaurant
        $restaurant = Restaurant::firstOrCreate(
            ['name' => 'The Pickle Jar'],
        );

        // Order data extracted from notes
        $orders = [
            [
                'notes' => 'About perfect',
                'items' => [
                    ['name' => 'Arrosto', 'quantity' => 2],
                    ['name' => 'Chicken', 'quantity' => 1],
                    ['name' => 'Lamb', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Too much - for 10 people should get 2.5m',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 3],
                ]
            ],
            [
                'notes' => 'Papa, Rodger, James, Michael, Jeff - little bit too much',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '9 people: Papa, Rodger, Patricia, Janet, Belinda, Michael, Awhina, Jeff - 1m left over',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                    ['name' => 'Chips', 'quantity' => 2],
                ]
            ],
            [
                'notes' => '10 people: Papa, Rodger, Patricia, Jacqui, Belinda, Michael, Awhina, Jeff, Atawhai - 4 slices left',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                    ['name' => 'Chips', 'quantity' => 2],
                ]
            ],
            [
                'notes' => '7 people: Papa, Rodger, Kevin, Michael, Jeff, Bel, Sage - Perfect',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 2],
                ]
            ],
            [
                'notes' => 'Mertilla Di Pollo, Speziato, Selvaggio, Amore, Margherita Amore Speziato',
                'items' => [
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Selvaggio', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Margherita Amore Speziato', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '11 people except Jacqui - pizza too many',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                    ['name' => 'Chips', 'quantity' => 2],
                ]
            ],
            [
                'notes' => 'Papa, Rodger, James, Michael, Jeff, Janet - little bit too much',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Menzo Bernese', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Michael, Jacqui, Papa, Jeff, Janet, Kevin - not enough (controversial)',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Michael, Jacqui, Papa, Jamie - too much but wanted leftovers',
                'items' => [
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Tricolore', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Michael, Kevin, Papa, Bel, Jan - 1m left over',
                'items' => [
                    ['name' => 'Pizza', 'quantity' => 15],
                ]
            ],
            [
                'notes' => 'Michael, Janet, Jacqui, Awhina, Atawhai, Kevin - 6 people',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Chips', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Menzo Bernese', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '7 people order',
                'items' => [
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Selvaggio', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '12 people order',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Ponsonby', 'quantity' => 1],
                    ['name' => 'Homewood', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '14 people: Belinda, Sage, Michael, Janet, Rodger, Kevin, Annaleise, Jamie, Atawhai, Bede, Patricia, Awhina, James, Jeff',
                'items' => [
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Ponsonby Road', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                    ['name' => 'Ponsonby', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Another order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Margherita', 'quantity' => 1],
                    ['name' => 'Amore', 'quantity' => 1],
                ]
            ],
            [
                'notes' => '9 people order',
                'items' => [
                    ['name' => 'Homewood', 'quantity' => 1],
                    ['name' => 'Speziato', 'quantity' => 1],
                    ['name' => 'Mertilla Di Pollo', 'quantity' => 1],
                    ['name' => 'Amore Messines', 'quantity' => 1],
                ]
            ],
            [
                'notes' => 'Final orders',
                'items' => [
                    ['name' => 'Birdwood', 'quantity' => 1],
                    ['name' => 'Monaghan', 'quantity' => 1],
                    ['name' => 'Hatton', 'quantity' => 1],
                ]
            ],
        ];

        // Create orders and their items
        foreach ($orders as $orderData) {
            $order = Order::create([
                'restaurant_id' => $restaurant->id,
                'content' => $orderData['notes'],
            ]);

            foreach ($orderData['items'] as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                ]);
            }
        }
    }
}
