<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_has_many_menus()
    {
        $restaurant = Restaurant::factory()->create();
        $menus = Menu::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

        $this->assertCount(3, $restaurant->menus);
        $this->assertInstanceOf(Menu::class, $restaurant->menus->first());
    }
}
