<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_item_belongs_to_menu()
    {
        $menu = Menu::factory()->create();
        $menuItem = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->assertInstanceOf(Menu::class, $menuItem->menu);
        $this->assertEquals($menu->id, $menuItem->menu->id);
    }

    public function test_available_scope_returns_only_available_items()
    {
        MenuItem::factory()->create(['is_available' => true]);
        MenuItem::factory()->create(['is_available' => false]);

        $availableItems = MenuItem::available()->get();

        $this->assertCount(1, $availableItems);
        $this->assertTrue($availableItems->first()->is_available);
    }

    public function test_by_section_scope_filters_by_section()
    {
        MenuItem::factory()->create(['section' => 'appetizers']);
        MenuItem::factory()->create(['section' => 'mains']);

        $appetizerItems = MenuItem::bySection('appetizers')->get();

        $this->assertCount(1, $appetizerItems);
        $this->assertEquals('appetizers', $appetizerItems->first()->section);
    }

    public function test_menu_item_casts_attributes_correctly()
    {
        $menuItem = MenuItem::factory()->create([
            'price' => '15.99',
            'order_index' => '1',
            'is_available' => 1
        ]);

        $this->assertEquals('15.99', $menuItem->price);
        $this->assertIsInt($menuItem->order_index);
        $this->assertIsBool($menuItem->is_available);
    }

    public function test_menu_item_fillable_attributes()
    {
        $attributes = [
            'menu_id' => 1,
            'name' => 'Test Item',
            'description' => 'Test Description',
            'price' => 10.99,
            'section' => 'mains',
            'order_index' => 1,
            'is_available' => true,
        ];

        $menuItem = new MenuItem($attributes);

        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, $menuItem->$key);
        }
    }
}