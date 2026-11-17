<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Seeds K.C Cafe's menu with the dishes, filter tags, variants and units from
 * the Menu Maker prototype, listed most-ordered first.
 */
class KcCafeMenuSeeder extends Seeder
{
    /**
     * [name, tags, variants, [unit, step, first quantity]]
     */
    private const DISHES = [
        ['Duck Meat Fried Rice', ['Duck', 'Rice']],
        ['Mapo Tofu', ['Tofu', 'Pork', 'Spicy']],
        ['Soy Sauce Chicken', ['Chicken'], null, ['whole', 0.5, 0.5]],
        ['Ho Fun', ['Beef', 'Noodles'], ['Beef Brisket', 'Beef']],
        ['Sweet and Sour', ['Pork', 'Seafood'], ['Pork', 'Fish Fillet']],
        ['Eggplant with Pork Mince', ['Pork', 'Veg']],
        ['Chinese Vegetables with Oyster Sauce', ['Veg']],
        ['Char Siu (BBQ Pork)', ['Pork', 'Roast & BBQ'], null, ['kg', 0.5, 0.5]],
        ['Wonton BBQ Pork Noodle Soup', ['Pork', 'Noodles', 'Soup', 'Dumplings']],
        ['Pan Fried Pork Dumplings', ['Pork', 'Dumplings'], null, ['pcs', 5, 10]],
        ['Beef Brisket Clay Pot', ['Beef', 'Claypot']],
        ['Orange Beef', ['Beef']],
        ['Mee Goreng', ['Noodles', 'Spicy']],
        ['Garlic Gai Lan', ['Veg']],
        ['Round Beans', ['Pork', 'Veg', 'Spicy'], ['Pork Mince', 'Dried Chilli']],
        ['Pork with Peking Sauce', ['Pork']],
        ['Fried Garlic Chicken', ['Chicken']],
        ['Belly Pork with Chef’s Special Sauce', ['Pork']],
        ['Singapore Noodles', ['Noodles']],
        ['Pork Chop', ['Pork'], ['Lemon Grass', 'Black Pepper Sauce']],
        ['KC Tofu', ['Tofu']],
        ['Roast Duck', ['Duck', 'Roast & BBQ'], null, ['whole', 0.5, 0.5]],
        ['Salt and Pepper King Prawns', ['Seafood']],
        ['Garlic & Oyster Sauce Claypot', ['Duck', 'Chicken', 'Claypot'], ['Duck', 'Chicken']],
    ];

    public function run(): void
    {
        $restaurant = Restaurant::where('name', 'like', 'K.C%')
            ->orWhere('name', 'like', 'KC Cafe%')
            ->orderBy('id')
            ->first()
            ?? Restaurant::create(['name' => 'K.C Cafe', 'google_place_id' => 'seed:kc-cafe']);

        $menu = Menu::updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'name' => 'Main Menu'],
            ['menu_type' => 'manual', 'is_active' => true],
        );

        foreach (self::DISHES as $i => $dish) {
            [$name, $tags, $variants, $unit] = $dish + [2 => null, 3 => null];

            $menu->menuItems()->updateOrCreate(['name' => $name], [
                'tags' => $tags,
                'variants' => $variants,
                'unit' => $unit[0] ?? null,
                'unit_step' => $unit[1] ?? null,
                'unit_start' => $unit[2] ?? null,
                'order_index' => $i,
                'is_available' => true,
            ]);
        }
    }
}
