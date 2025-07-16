<?php

namespace Tests\Unit\Models;

use App\Models\ScrapingLog;
use App\Models\Restaurant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScrapingLogValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_validation_rules()
    {
        $rules = ScrapingLog::validationRules();
        
        $this->assertArrayHasKey('restaurant_id', $rules);
        $this->assertArrayHasKey('scraping_type', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('scraped_at', $rules);
        
        $this->assertStringContainsString('required', $rules['restaurant_id']);
        $this->assertStringContainsString('exists:restaurants,id', $rules['restaurant_id']);
        $this->assertStringContainsString('in:menu,full', $rules['scraping_type']);
        $this->assertStringContainsString('in:success,failed,partial', $rules['status']);
        $this->assertStringContainsString('required', $rules['scraped_at']);
    }

    /** @test */
    public function it_checks_if_scraping_was_successful()
    {
        $log = new ScrapingLog();
        $log->status = 'success';
        
        $this->assertTrue($log->wasSuccessful());
        
        $log->status = 'failed';
        $this->assertFalse($log->wasSuccessful());
    }

    /** @test */
    public function it_checks_if_scraping_failed()
    {
        $log = new ScrapingLog();
        $log->status = 'failed';
        
        $this->assertTrue($log->hasFailed());
        
        $log->status = 'success';
        $this->assertFalse($log->hasFailed());
    }

    /** @test */
    public function it_calculates_total_items_processed()
    {
        $log = new ScrapingLog();
        $log->items_created = 5;
        $log->items_updated = 3;
        
        $this->assertEquals(8, $log->getTotalItemsProcessed());
        
        $log->items_created = null;
        $log->items_updated = 3;
        
        $this->assertEquals(3, $log->getTotalItemsProcessed());
    }

    /** @test */
    public function it_formats_duration_correctly()
    {
        $log = new ScrapingLog();
        $log->duration_seconds = 45;
        
        $this->assertEquals('45 seconds', $log->getFormattedDuration());
        
        $log->duration_seconds = 125;
        $this->assertEquals('2m 5s', $log->getFormattedDuration());
        
        $log->duration_seconds = null;
        $this->assertEquals('Unknown', $log->getFormattedDuration());
    }

    /** @test */
    public function it_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $log = ScrapingLog::factory()->create(['restaurant_id' => $restaurant->id]);
        
        $this->assertInstanceOf(Restaurant::class, $log->restaurant);
        $this->assertEquals($restaurant->id, $log->restaurant->id);
    }
}