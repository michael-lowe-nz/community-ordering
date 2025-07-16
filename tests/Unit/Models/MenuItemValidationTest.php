<?php

namespace Tests\Unit\Models;

use App\Models\MenuItem;
use App\Models\Menu;
use App\Models\Restaurant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuItemValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_validation_rules()
    {
        $rules = MenuItem::validationRules();
        
        $this->assertArrayHasKey('menu_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('is_available', $rules);
        
        $this->assertStringContainsString('required', $rules['menu_id']);
        $this->assertStringContainsString('exists:menus,id', $rules['menu_id']);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('numeric', $rules['price']);
        $this->assertStringContainsString('boolean', $rules['is_available']);
    }

    /** @test */
    public function it_checks_if_menu_item_is_available()
    {
        $menuItem = new MenuItem();
        $menuItem->is_available = true;
        
        $this->assertTrue($menuItem->isAvailable());
        
        $menuItem->is_available = false;
        $this->assertFalse($menuItem->isAvailable());
    }

    /** @test */
    public function it_checks_if_menu_item_has_price()
    {
        $menuItem = new MenuItem();
        $menuItem->price = 15.99;
        
        $this->assertTrue($menuItem->hasPrice());
        
        $menuItem->price = 0;
        $this->assertFalse($menuItem->hasPrice());
        
        $menuItem->price = null;
        $this->assertFalse($menuItem->hasPrice());
    }

    /** @test */
    public function it_formats_price_correctly()
    {
        $menuItem = new MenuItem();
        $menuItem->price = 15.99;
        
        $this->assertEquals('$15.99', $menuItem->getFormattedPrice());
        
        $menuItem->price = null;
        $this->assertEquals('Price not available', $menuItem->getFormattedPrice());
    }

    /** @test */
    public function it_gets_section_name_with_default()
    {
        $menuItem = new MenuItem();
        $menuItem->section = 'Appetizers';
        
        $this->assertEquals('Appetizers', $menuItem->getSectionName());
        
        $menuItem->section = null;
        $this->assertEquals('Other', $menuItem->getSectionName());
    }

    /** @test */
    public function it_belongs_to_menu()
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create(['restaurant_id' => $restaurant->id]);
        $menuItem = MenuItem::factory()->create(['menu_id' => $menu->id]);
        
        $this->assertInstanceOf(Menu::class, $menuItem->menu);
        $this->assertEquals($menu->id, $menuItem->menu->id);
    }
}