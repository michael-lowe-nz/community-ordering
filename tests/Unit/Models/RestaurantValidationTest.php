<?php

namespace Tests\Unit\Models;

use App\Models\Restaurant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestaurantValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_validation_rules()
    {
        $rules = Restaurant::validationRules();
        
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('menu_url', $rules);
        $this->assertArrayHasKey('menu_scraping_enabled', $rules);
        $this->assertArrayHasKey('menu_scrape_frequency', $rules);
        
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('url', $rules['menu_url']);
        $this->assertStringContainsString('boolean', $rules['menu_scraping_enabled']);
        $this->assertStringContainsString('in:daily,weekly,monthly', $rules['menu_scrape_frequency']);
    }

    /** @test */
    public function it_validates_menu_url_correctly()
    {
        $restaurant = new Restaurant();
        $restaurant->menu_url = 'https://example.com/menu';
        
        $this->assertTrue($restaurant->hasValidMenuUrl());
        
        $restaurant->menu_url = 'invalid-url';
        $this->assertFalse($restaurant->hasValidMenuUrl());
        
        $restaurant->menu_url = null;
        $this->assertFalse($restaurant->hasValidMenuUrl());
    }

    /** @test */
    public function it_checks_if_ready_for_scraping()
    {
        $restaurant = new Restaurant();
        $restaurant->menu_scraping_enabled = true;
        $restaurant->menu_url = 'https://example.com/menu';
        
        $this->assertTrue($restaurant->isReadyForScraping());
        
        $restaurant->menu_scraping_enabled = false;
        $this->assertFalse($restaurant->isReadyForScraping());
        
        $restaurant->menu_scraping_enabled = true;
        $restaurant->menu_url = null;
        $this->assertFalse($restaurant->isReadyForScraping());
    }
}