<?php

namespace Tests\Unit\Models;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapingLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_scraping_log_belongs_to_restaurant()
    {
        $restaurant = Restaurant::factory()->create();
        $log = ScrapingLog::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->assertInstanceOf(Restaurant::class, $log->restaurant);
        $this->assertEquals($restaurant->id, $log->restaurant->id);
    }

    public function test_successful_scope_returns_only_successful_logs()
    {
        ScrapingLog::factory()->create(['status' => 'success']);
        ScrapingLog::factory()->create(['status' => 'failed']);

        $successfulLogs = ScrapingLog::successful()->get();

        $this->assertCount(1, $successfulLogs);
        $this->assertEquals('success', $successfulLogs->first()->status);
    }

    public function test_failed_scope_returns_only_failed_logs()
    {
        ScrapingLog::factory()->create(['status' => 'success']);
        ScrapingLog::factory()->create(['status' => 'failed']);

        $failedLogs = ScrapingLog::failed()->get();

        $this->assertCount(1, $failedLogs);
        $this->assertEquals('failed', $failedLogs->first()->status);
    }

    public function test_recent_scope_returns_logs_within_specified_days()
    {
        ScrapingLog::factory()->create(['scraped_at' => now()->subDays(5)]);
        ScrapingLog::factory()->create(['scraped_at' => now()->subDays(10)]);

        $recentLogs = ScrapingLog::recent(7)->get();

        $this->assertCount(1, $recentLogs);
    }

    public function test_scraping_log_casts_attributes_correctly()
    {
        $log = ScrapingLog::factory()->create([
            'scraped_at' => '2024-01-01 12:00:00',
            'items_found' => '5',
            'items_created' => '3',
            'items_updated' => '2',
            'duration_seconds' => '30'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $log->scraped_at);
        $this->assertIsInt($log->items_found);
        $this->assertIsInt($log->items_created);
        $this->assertIsInt($log->items_updated);
        $this->assertIsInt($log->duration_seconds);
    }
}