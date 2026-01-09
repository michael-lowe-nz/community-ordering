<?php

namespace Tests\Unit\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use App\Models\Menu;
use App\Services\MenuScrapingService;
use App\Services\WebScrapingServiceInterface;
use App\Services\MenuParsingServiceInterface;
use App\Services\MenuStorageServiceInterface;
use App\Services\ScrapingLogServiceInterface;
use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuScrapingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $webScrapingService;
    protected $menuParsingService;
    protected $menuStorageService;
    protected $scrapingLogService;
    protected $service;
    protected $restaurant;
    protected $scrapingLog;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->webScrapingService = Mockery::mock(WebScrapingServiceInterface::class);
        $this->menuParsingService = Mockery::mock(MenuParsingServiceInterface::class);
        $this->menuStorageService = Mockery::mock(MenuStorageServiceInterface::class);
        $this->scrapingLogService = Mockery::mock(ScrapingLogServiceInterface::class);
        
        $this->service = new MenuScrapingService(
            $this->webScrapingService,
            $this->menuParsingService,
            $this->menuStorageService,
            $this->scrapingLogService
        );
        
        // Create a test restaurant
        $this->restaurant = Restaurant::factory()->create([
            'menu_url' => 'https://example.com/menu',
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'daily',
            'last_menu_scrape' => null
        ]);
        
        // Create a test scraping log
        $this->scrapingLog = ScrapingLog::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'scraping_type' => 'manual',
            'status' => 'partial' // Using 'partial' instead of 'in_progress' as the database only supports 'success', 'failed', 'partial'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_scrape_restaurant_skips_when_not_enabled()
    {
        // Update restaurant to disable scraping
        $this->restaurant->menu_scraping_enabled = false;
        $this->restaurant->save();
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('skipped', $result['status']);
        $this->assertEquals('Restaurant is not configured for scraping', $result['message']);
    }

    public function test_scrape_restaurant_skips_when_not_due()
    {
        // Update restaurant to have recent scrape
        $this->restaurant->last_menu_scrape = now()->subHours(12); // Less than daily frequency
        $this->restaurant->save();
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('skipped', $result['status']);
        $this->assertEquals('Restaurant is not due for scraping yet', $result['message']);
    }

    public function test_scrape_restaurant_fails_when_url_inaccessible()
    {
        $this->scrapingLogService
            ->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);
        
        $this->webScrapingService
            ->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(false);
        
        $this->scrapingLogService
            ->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'url_inaccessible')
            ->andReturn($this->scrapingLog);
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('URL is not accessible', $result['message']);
    }

    public function test_scrape_restaurant_fails_when_robots_txt_disallowed()
    {
        $this->scrapingLogService
            ->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);
        
        $this->webScrapingService
            ->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('respectsRobotsTxt')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(false);
        
        $this->scrapingLogService
            ->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'robots_txt_disallowed')
            ->andReturn($this->scrapingLog);
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('URL does not respect robots.txt', $result['message']);
    }

    public function test_scrape_restaurant_fails_when_content_fetch_fails()
    {
        $this->scrapingLogService
            ->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);
        
        $this->webScrapingService
            ->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('respectsRobotsTxt')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('fetchMenuContent')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(null);
        
        $this->scrapingLogService
            ->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'content_fetch_failed')
            ->andReturn($this->scrapingLog);
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Failed to fetch menu content', $result['message']);
    }

    public function test_scrape_restaurant_fails_when_no_items_found()
    {
        $this->scrapingLogService
            ->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);
        
        $this->webScrapingService
            ->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('respectsRobotsTxt')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('fetchMenuContent')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn([
                'content' => '<html><body>No menu items here</body></html>',
                'type' => 'html'
            ]);
        
        $this->menuParsingService
            ->shouldReceive('parseMenuContent')
            ->once()
            ->with('<html><body>No menu items here</body></html>', $this->restaurant->menu_url, 'html')
            ->andReturn([
                'items' => [],
                'sections' => [],
                'total_items' => 0
            ]);
        
        $this->scrapingLogService
            ->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'no_items_found')
            ->andReturn($this->scrapingLog);
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('No menu items found in content', $result['message']);
    }

    public function test_scrape_restaurant_succeeds_with_valid_content()
    {
        $menuItems = [
            [
                'name' => 'Burger',
                'description' => 'Delicious burger',
                'price' => 12.99,
                'section' => 'Main'
            ],
            [
                'name' => 'Fries',
                'description' => 'Crispy fries',
                'price' => 5.99,
                'section' => 'Sides'
            ]
        ];
        
        $menu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Main Menu',
            'menu_type' => 'scraped',
            'is_active' => true
        ]);
        
        $this->scrapingLogService
            ->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);
        
        $this->webScrapingService
            ->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('respectsRobotsTxt')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);
        
        $this->webScrapingService
            ->shouldReceive('fetchMenuContent')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn([
                'content' => '<html><body>Menu content</body></html>',
                'type' => 'html'
            ]);
        
        $this->menuParsingService
            ->shouldReceive('parseMenuContent')
            ->once()
            ->with('<html><body>Menu content</body></html>', $this->restaurant->menu_url, 'html')
            ->andReturn([
                'items' => $menuItems,
                'sections' => ['Main', 'Sides'],
                'total_items' => 2
            ]);
        
        $this->menuStorageService
            ->shouldReceive('processMenuData')
            ->once()
            ->with($this->restaurant, Mockery::any(), $menuItems)
            ->andReturn([
                'menu' => $menu,
                'items_created' => 2,
                'items_updated' => 0
            ]);
        
        $this->scrapingLogService
            ->shouldReceive('completeScraping')
            ->once()
            ->with($this->scrapingLog, 2, 2, 0)
            ->andReturn($this->scrapingLog);
        
        $result = $this->service->scrapeRestaurant($this->restaurant);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Menu scraped successfully', $result['message']);
        $this->assertEquals(2, $result['items_found']);
        $this->assertEquals(2, $result['items_created']);
        $this->assertEquals(0, $result['items_updated']);
        
        // Verify restaurant last_menu_scrape was updated
        $this->restaurant->refresh();
        $this->assertNotNull($this->restaurant->last_menu_scrape);
    }

    public function test_scrape_multiple_restaurants()
    {
        $restaurant2 = Restaurant::factory()->create([
            'menu_url' => 'https://example.org/menu',
            'menu_scraping_enabled' => true
        ]);
        
        // Create a mock of the MenuScrapingService
        $mockService = Mockery::mock(MenuScrapingService::class, [
            $this->webScrapingService,
            $this->menuParsingService,
            $this->menuStorageService,
            $this->scrapingLogService
        ])->makePartial();
        
        // Mock the scrapeRestaurant method to avoid actual scraping
        $mockService->shouldReceive('scrapeRestaurant')
            ->with(Mockery::type(Restaurant::class), false)
            ->andReturnUsing(function ($restaurant) {
                return [
                    'status' => $restaurant->id % 2 === 0 ? 'success' : 'failed',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name
                ];
            });
        
        // Call the method we want to test
        $results = $mockService->scrapeMultipleRestaurants([$this->restaurant->id, $restaurant2->id]);
        
        // Verify the results
        $this->assertIsArray($results);
        $this->assertArrayHasKey($this->restaurant->id, $results);
        $this->assertArrayHasKey($restaurant2->id, $results);
        
        // The status should be based on our mock implementation
        $this->assertEquals($this->restaurant->id % 2 === 0 ? 'success' : 'failed', $results[$this->restaurant->id]['status']);
        $this->assertEquals($restaurant2->id % 2 === 0 ? 'success' : 'failed', $results[$restaurant2->id]['status']);
    }

    public function test_get_scraping_history()
    {
        $this->scrapingLogService
            ->shouldReceive('getScrapingHistory')
            ->once()
            ->with($this->restaurant, 5)
            ->andReturn(collect([
                ScrapingLog::factory()->create(['restaurant_id' => $this->restaurant->id]),
                ScrapingLog::factory()->create(['restaurant_id' => $this->restaurant->id])
            ]));
        
        $history = $this->service->getScrapingHistory($this->restaurant, 5);
        
        $this->assertCount(2, $history);
    }

    public function test_get_restaurants_needing_scraping()
    {
        // Create restaurants with different scraping needs
        $dueRestaurant = Restaurant::factory()->create([
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'daily',
            'last_menu_scrape' => now()->subDays(2)
        ]);
        
        $notDueRestaurant = Restaurant::factory()->create([
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'weekly',
            'last_menu_scrape' => now()->subDays(3)
        ]);
        
        $disabledRestaurant = Restaurant::factory()->create([
            'menu_scraping_enabled' => false,
            'menu_scrape_frequency' => 'daily',
            'last_menu_scrape' => now()->subDays(2)
        ]);
        
        $restaurants = $this->service->getRestaurantsNeedingScraping();
        
        $this->assertTrue($restaurants->contains($dueRestaurant->id));
        $this->assertFalse($restaurants->contains($notDueRestaurant->id));
        $this->assertFalse($restaurants->contains($disabledRestaurant->id));
    }

    public function test_get_scraping_status_summary()
    {
        $this->scrapingLogService
            ->shouldReceive('getRecentStatistics')
            ->once()
            ->with(7)
            ->andReturn([
                'total_scrapes' => 10,
                'successful_scrapes' => 8,
                'failed_scrapes' => 2,
                'partial_scrapes' => 0,
                'success_rate' => 80,
                'total_items_found' => 100,
                'total_items_created' => 80,
                'total_items_updated' => 20
            ]);
        
        $this->scrapingLogService
            ->shouldReceive('getErrorStatistics')
            ->once()
            ->with(7)
            ->andReturn([
                'url_inaccessible' => 1,
                'no_items_found' => 1
            ]);
        
        $this->scrapingLogService
            ->shouldReceive('getPerformanceMetrics')
            ->once()
            ->with(7)
            ->andReturn([
                'avg_duration' => 5.2,
                'max_duration' => 12.5,
                'min_duration' => 2.1
            ]);
        
        $summary = $this->service->getScrapingStatusSummary();
        
        $this->assertEquals(7, $summary['period_days']);
        $this->assertEquals(10, $summary['total_scrapes']);
        $this->assertEquals(8, $summary['successful_scrapes']);
        $this->assertEquals(2, $summary['failed_scrapes']);
        $this->assertEquals(80, $summary['success_rate']);
        $this->assertEquals(100, $summary['total_items_found']);
        $this->assertEquals(80, $summary['total_items_created']);
        $this->assertEquals(20, $summary['total_items_updated']);
        $this->assertArrayHasKey('error_categories', $summary);
        $this->assertArrayHasKey('performance', $summary);
    }
}