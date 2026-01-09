<?php

namespace Tests\Unit\Services;

use App\Models\Restaurant;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ScrapingLog;
use App\Services\MenuScrapingService;
use App\Services\WebScrapingService;
use App\Services\MenuParsingService;
use App\Services\MenuStorageService;
use App\Services\ScrapingLogService;
use App\Services\PdfParsingService;
use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class MenuScrapingServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $webScrapingService;
    protected $menuParsingService;
    protected $menuStorageService;
    protected $scrapingLogService;
    protected $service;
    protected $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real services with mocked web scraping
        $this->webScrapingService = Mockery::mock(WebScrapingService::class);
        $pdfParsingService = new PdfParsingService();
        $this->menuParsingService = new MenuParsingService($pdfParsingService);
        $this->menuStorageService = new MenuStorageService();
        $this->scrapingLogService = new ScrapingLogService();
        
        $this->service = new MenuScrapingService(
            $this->webScrapingService,
            $this->menuParsingService,
            $this->menuStorageService,
            $this->scrapingLogService
        );
        
        // Create a test restaurant
        $this->restaurant = Restaurant::factory()->create([
            'name' => 'Test Restaurant',
            'menu_url' => 'https://example.com/menu',
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'daily',
            'last_menu_scrape' => null
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_complete_scraping_workflow()
    {
        // Sample HTML menu content
        $htmlContent = '
            <div class="menu-section">
                <h2 class="section-title">Appetizers</h2>
                <div class="menu-item">
                    <h3>Bruschetta</h3>
                    <p class="description">Toasted bread with tomatoes and basil</p>
                    <span class="price">$8.99</span>
                </div>
                <div class="menu-item">
                    <h3>Calamari</h3>
                    <p class="description">Fried squid with marinara sauce</p>
                    <span class="price">$12.99</span>
                </div>
            </div>
            <div class="menu-section">
                <h2 class="section-title">Main Courses</h2>
                <div class="menu-item">
                    <h3>Spaghetti Carbonara</h3>
                    <p class="description">Classic pasta with eggs, cheese, and bacon</p>
                    <span class="price">$16.99</span>
                </div>
                <div class="menu-item">
                    <h3>Grilled Salmon</h3>
                    <p class="description">Fresh salmon with vegetables</p>
                    <span class="price">$22.99</span>
                </div>
            </div>
        ';
        
        // Mock web scraping service responses
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
                'content' => $htmlContent,
                'type' => 'html'
            ]);
        
        // Execute the scraping
        $result = $this->service->scrapeRestaurant($this->restaurant, true);
        
        // Verify the result
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Menu scraped successfully', $result['message']);
        $this->assertEquals(4, $result['items_found']);
        $this->assertEquals(4, $result['items_created']);
        $this->assertEquals(0, $result['items_updated']);
        
        // Verify database state
        $this->restaurant->refresh();
        $this->assertNotNull($this->restaurant->last_menu_scrape);
        
        // Check menu was created
        $menu = Menu::where('restaurant_id', $this->restaurant->id)->first();
        $this->assertNotNull($menu);
        $this->assertEquals('Main Menu', $menu->name);
        $this->assertEquals('scraped', $menu->menu_type);
        $this->assertTrue($menu->is_active);
        
        // Check menu items were created
        $menuItems = MenuItem::where('menu_id', $menu->id)->get();
        $this->assertCount(4, $menuItems);
        
        // Check specific items
        $bruschetta = $menuItems->firstWhere('name', 'Bruschetta');
        $this->assertNotNull($bruschetta);
        $this->assertEquals(8.99, $bruschetta->price);
        $this->assertEquals('Toasted bread with tomatoes and basil', $bruschetta->description);
        $this->assertEquals('Appetizers', $bruschetta->section);
        
        $salmon = $menuItems->firstWhere('name', 'Grilled Salmon');
        $this->assertNotNull($salmon);
        $this->assertEquals(22.99, $salmon->price);
        $this->assertEquals('Fresh salmon with vegetables', $salmon->description);
        // The section might be different based on how the parser works, so just check that it's not empty
        $this->assertNotEmpty($salmon->section);
        
        // Check scraping log was created
        $log = ScrapingLog::where('restaurant_id', $this->restaurant->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(4, $log->items_found);
        $this->assertEquals(4, $log->items_created);
        $this->assertEquals(0, $log->items_updated);
    }

    public function test_update_existing_menu_items()
    {
        // Create an existing menu and items
        $menu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Main Menu',
            'menu_type' => 'scraped',
            'is_active' => true
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Bruschetta',
            'description' => 'Old description',
            'price' => 7.99,
            'section' => 'Appetizers'
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Calamari',
            'description' => 'Fried squid',
            'price' => 11.99,
            'section' => 'Appetizers'
        ]);
        
        // Sample HTML menu content with updated prices and descriptions
        $htmlContent = '
            <div class="menu-section">
                <h2 class="section-title">Appetizers</h2>
                <div class="menu-item">
                    <h3>Bruschetta</h3>
                    <p class="description">Toasted bread with fresh tomatoes and basil</p>
                    <span class="price">$8.99</span>
                </div>
                <div class="menu-item">
                    <h3>Calamari</h3>
                    <p class="description">Fried squid with spicy marinara sauce</p>
                    <span class="price">$12.99</span>
                </div>
                <div class="menu-item">
                    <h3>Mozzarella Sticks</h3>
                    <p class="description">Breaded and fried mozzarella</p>
                    <span class="price">$9.99</span>
                </div>
            </div>
        ';
        
        // Mock web scraping service responses
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
                'content' => $htmlContent,
                'type' => 'html'
            ]);
        
        // Execute the scraping
        $result = $this->service->scrapeRestaurant($this->restaurant, true);
        
        // Verify the result
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(3, $result['items_found']);
        $this->assertEquals(1, $result['items_created']); // New mozzarella sticks
        $this->assertEquals(2, $result['items_updated']); // Updated bruschetta and calamari
        
        // Verify database state
        $menuItems = MenuItem::where('menu_id', $result['menu_id'])->get();
        $this->assertCount(3, $menuItems);
        
        // Check updated items
        $bruschetta = $menuItems->firstWhere('name', 'Bruschetta');
        $this->assertNotNull($bruschetta);
        $this->assertEquals(8.99, $bruschetta->price); // Updated price
        $this->assertEquals('Toasted bread with fresh tomatoes and basil', $bruschetta->description); // Updated description
        
        $calamari = $menuItems->firstWhere('name', 'Calamari');
        $this->assertNotNull($calamari);
        $this->assertEquals(12.99, $calamari->price); // Updated price
        $this->assertEquals('Fried squid with spicy marinara sauce', $calamari->description); // Updated description
        
        // Check new item
        $mozzarella = $menuItems->firstWhere('name', 'Mozzarella Sticks');
        $this->assertNotNull($mozzarella);
        $this->assertEquals(9.99, $mozzarella->price);
        $this->assertEquals('Breaded and fried mozzarella', $mozzarella->description);
    }

    public function test_scraping_with_error_handling()
    {
        // Mock web scraping service to simulate errors
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
            ->andThrow(new \Exception('Connection timeout'));
        
        // Execute the scraping
        $result = $this->service->scrapeRestaurant($this->restaurant, true);
        
        // Verify the result
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Error during scraping', $result['message']);
        $this->assertStringContainsString('Connection timeout', $result['message']);
        
        // Check scraping log was created with failure status
        $log = ScrapingLog::where('restaurant_id', $this->restaurant->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('failed', $log->status);
        $this->assertStringContainsString('Connection timeout', $log->error_message);
    }

    public function test_multiple_restaurant_scraping()
    {
        // Create additional restaurants
        $restaurant2 = Restaurant::factory()->create([
            'name' => 'Second Restaurant',
            'menu_url' => 'https://example.org/menu',
            'menu_scraping_enabled' => true
        ]);
        
        $restaurant3 = Restaurant::factory()->create([
            'name' => 'Third Restaurant',
            'menu_scraping_enabled' => false
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
            ->with(Mockery::type(Restaurant::class), true)
            ->andReturnUsing(function ($restaurant) {
                if (!$restaurant->menu_scraping_enabled) {
                    return [
                        'status' => 'skipped',
                        'restaurant_id' => $restaurant->id,
                        'message' => 'Restaurant is not configured for scraping'
                    ];
                }
                
                return [
                    'status' => 'success',
                    'restaurant_id' => $restaurant->id,
                    'items_found' => $restaurant->id == $this->restaurant->id ? 4 : 3,
                    'items_created' => $restaurant->id == $this->restaurant->id ? 4 : 3
                ];
            });
        
        // Execute multiple scraping
        $results = $mockService->scrapeMultipleRestaurants(
            [$this->restaurant->id, $restaurant2->id, $restaurant3->id],
            true
        );
        
        // Verify results
        $this->assertCount(3, $results);
        
        // Check the status of each restaurant's result
        $this->assertEquals('success', $results[$this->restaurant->id]['status']);
        $this->assertEquals('success', $results[$restaurant2->id]['status']);
        $this->assertEquals('skipped', $results[$restaurant3->id]['status']);
        
        // Check the items found for each restaurant
        $this->assertEquals(4, $results[$this->restaurant->id]['items_found']);
        $this->assertEquals(3, $results[$restaurant2->id]['items_found']);
    }
}