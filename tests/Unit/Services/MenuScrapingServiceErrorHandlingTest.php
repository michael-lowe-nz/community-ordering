<?php

namespace Tests\Unit\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use App\Services\MenuContentValidationService;
use App\Services\MenuParsingServiceInterface;
use App\Services\MenuScrapingService;
use App\Services\MenuStorageServiceInterface;
use App\Services\ScrapingLogServiceInterface;
use App\Services\WebScrapingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class MenuScrapingServiceErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $webScrapingService;
    protected $menuParsingService;
    protected $menuStorageService;
    protected $scrapingLogService;
    protected $menuContentValidationService;
    protected $menuScrapingService;
    protected $restaurant;
    protected $scrapingLog;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->webScrapingService = Mockery::mock(WebScrapingServiceInterface::class);
        $this->menuParsingService = Mockery::mock(MenuParsingServiceInterface::class);
        $this->menuStorageService = Mockery::mock(MenuStorageServiceInterface::class);
        $this->scrapingLogService = Mockery::mock(ScrapingLogServiceInterface::class);
        $this->menuContentValidationService = Mockery::mock(MenuContentValidationService::class);

        // Create the service with mocked dependencies
        $this->menuScrapingService = new MenuScrapingService(
            $this->webScrapingService,
            $this->menuParsingService,
            $this->menuStorageService,
            $this->scrapingLogService,
            $this->menuContentValidationService
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
            'status' => 'partial',
            'scraped_at' => now()
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_inaccessible_url()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(false);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'url_inaccessible')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('URL is not accessible', $result['message']);
        $this->assertEquals($this->restaurant->id, $result['restaurant_id']);
    }

    /** @test */
    public function it_handles_robots_txt_disallowed()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->with($this->restaurant, 'manual')
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(false);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'robots_txt_disallowed')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('URL does not respect robots.txt', $result['message']);
    }

    /** @test */
    public function it_handles_content_fetch_failure()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->with($this->restaurant->menu_url)
            ->andReturn(null);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'content_fetch_failed')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Failed to fetch menu content', $result['message']);
    }

    /** @test */
    public function it_handles_no_menu_items_found()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->andReturn([
                'content' => 'html content',
                'type' => 'html'
            ]);

        $this->menuParsingService->shouldReceive('parseMenuContent')
            ->once()
            ->andReturn([
                'items' => []
            ]);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'no_items_found')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('No menu items found in content', $result['message']);
    }

    /** @test */
    public function it_handles_invalid_menu_structure()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->andReturn([
                'content' => 'html content',
                'type' => 'html'
            ]);

        $this->menuParsingService->shouldReceive('parseMenuContent')
            ->once()
            ->andReturn([
                'items' => ['item1', 'item2']
            ]);

        $this->menuContentValidationService->shouldReceive('validateMenuStructure')
            ->once()
            ->andReturn([
                'is_valid' => false,
                'errors' => ['Invalid menu structure']
            ]);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'validation_error')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Invalid menu structure', $result['message']);
        $this->assertArrayHasKey('validation_errors', $result);
    }

    /** @test */
    public function it_handles_all_items_failing_validation()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->andReturn([
                'content' => 'html content',
                'type' => 'html'
            ]);

        $this->menuParsingService->shouldReceive('parseMenuContent')
            ->once()
            ->andReturn([
                'items' => ['item1', 'item2']
            ]);

        $this->menuContentValidationService->shouldReceive('validateMenuStructure')
            ->once()
            ->andReturn([
                'is_valid' => true,
                'errors' => []
            ]);

        $this->menuContentValidationService->shouldReceive('validateAndSanitizeMenuItems')
            ->once()
            ->andReturn([
                'items' => [],
                'errors' => ['Item validation failed'],
                'total_valid' => 0,
                'total_invalid' => 2
            ]);

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'validation_error')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('All menu items failed validation', $result['message']);
        $this->assertArrayHasKey('validation_errors', $result);
    }

    /** @test */
    public function it_handles_partial_validation_success()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->andReturn([
                'content' => 'html content',
                'type' => 'html'
            ]);

        $this->menuParsingService->shouldReceive('parseMenuContent')
            ->once()
            ->andReturn([
                'items' => ['item1', 'item2', 'item3']
            ]);

        $this->menuContentValidationService->shouldReceive('validateMenuStructure')
            ->once()
            ->andReturn([
                'is_valid' => true,
                'errors' => []
            ]);

        $validatedItems = [
            ['name' => 'Item 1', 'price' => 9.99],
            ['name' => 'Item 2', 'price' => 12.99]
        ];

        $this->menuContentValidationService->shouldReceive('validateAndSanitizeMenuItems')
            ->once()
            ->andReturn([
                'items' => $validatedItems,
                'errors' => ['One item failed validation'],
                'total_valid' => 2,
                'total_invalid' => 1
            ]);

        // Mock the database transaction
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $this->menuStorageService->shouldReceive('processMenuData')
            ->once()
            ->andReturn([
                'menu' => (object)['id' => 1],
                'items_created' => 2,
                'items_updated' => 0
            ]);

        $this->scrapingLogService->shouldReceive('partialScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('partial', $result['status']);
        $this->assertStringContainsString('validation issues', $result['message']);
        $this->assertEquals(3, $result['items_found']); // Total items including invalid ones
        $this->assertEquals(2, $result['items_valid']); // Valid items
        $this->assertEquals(1, $result['items_invalid']); // Invalid items
    }

    /** @test */
    public function it_handles_database_errors()
    {
        // Setup mocks
        $this->scrapingLogService->shouldReceive('startScraping')
            ->once()
            ->andReturn($this->scrapingLog);

        $this->webScrapingService->shouldReceive('isUrlAccessible')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('respectsRobotsTxt')
            ->once()
            ->andReturn(true);

        $this->webScrapingService->shouldReceive('fetchMenuContent')
            ->once()
            ->andReturn([
                'content' => 'html content',
                'type' => 'html'
            ]);

        $this->menuParsingService->shouldReceive('parseMenuContent')
            ->once()
            ->andReturn([
                'items' => ['item1', 'item2']
            ]);

        $this->menuContentValidationService->shouldReceive('validateMenuStructure')
            ->once()
            ->andReturn([
                'is_valid' => true,
                'errors' => []
            ]);

        $validatedItems = [
            ['name' => 'Item 1', 'price' => 9.99],
            ['name' => 'Item 2', 'price' => 12.99]
        ];

        $this->menuContentValidationService->shouldReceive('validateAndSanitizeMenuItems')
            ->once()
            ->andReturn([
                'items' => $validatedItems,
                'errors' => [],
                'total_valid' => 2,
                'total_invalid' => 0
            ]);

        // Mock the database transaction with error
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $this->menuStorageService->shouldReceive('processMenuData')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->scrapingLogService->shouldReceive('failScraping')
            ->once()
            ->with($this->scrapingLog, Mockery::any(), 'storage_error')
            ->andReturn($this->scrapingLog);

        // Execute the service
        $result = $this->menuScrapingService->scrapeRestaurant($this->restaurant, true);

        // Assert the result
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Database error', $result['message']);
        $this->assertEquals('storage_error', $result['error_type']);
    }
}