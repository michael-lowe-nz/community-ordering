<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Restaurant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_validation_rules()
    {
        $rules = Menu::validationRules();
        
        $this->assertArrayHasKey('restaurant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('menu_type', $rules);
        $this->assertArrayHasKey('is_active', $rules);
        
        $this->assertStringContainsString('required', $rules['restaurant_id']);
        $this->assertStringContainsString('exists:restaurants,id', $rules['restaurant_id']);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('in:scraped,manual,seasonal', $rules['menu_type']);
        $this->assertStringContainsString('boolean', $rules['is_active']);
    }

    /** @test */
    public function it_checks_if_menu_is_active()
    {
        $menu = new Menu();
        $menu->is_active = true;
        
        $this->assertTrue($menu->isActive());
        
        $menu->is_active = false;
        $this->assertFalse($menu->isActive());
    }

    /** @test */
    public function it_checks_if_menu_is_scraped()
    {
        $menu = new Menu();
        $menu->menu_type = 'scraped';
        
        $this->assertTrue($menu->isScraped());
        
        $menu->menu_type = 'manual';
        $this->assertFalse($menu->isScraped());
    }

    /** @test */
    public function it_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $menu = Menu::factory()->create(['restaurant_id' => $restaurant->id]);
        
        $this->assertInstanceOf(Restaurant::class, $menu->restaurant);
        $this->assertEquals($restaurant->id, $menu->restaurant->id);
    }
}