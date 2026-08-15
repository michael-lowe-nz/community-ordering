<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

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
            $order->items()->delete();
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

        $items = [
            'Fries',
            'Onion Rings',
            'Arrosto',
            'Beauchamp',
            'Jalapeno Bacon',
        ];

        foreach ($items as $index => $itemName) {
            $menu->menuItems()->create([
                'name' => $itemName,
                'description' => null,
                'price' => null,
                'section' => 'Sides',
                'order_index' => $index + 1,
                'is_available' => true,
            ]);
        }
    }
}
