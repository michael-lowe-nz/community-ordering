<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use App\Models\User;
use App\Services\MenuScrapingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuScrapingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
        
        // Create regular user
        $this->regularUser = User::factory()->create([
            'is_admin' => false,
        ]);
        
        // Create test restaurant
        $this->restaurant = Restaurant::factory()->create([
            'menu_url' => 'https://example.com/menu',
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'weekly',
        ]);
        
        // Mock the menu scraping service
        $this->menuScrapingService = Mockery::mock(MenuScrapingServiceInterface::class);
        $this->app->instance(MenuScrapingServiceInterface::class, $this->menuScrapingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function admin_can_access_menu_scraping_dashboard()
    {
        $this->menuScrapingService->shouldReceive('getRestaurantsNeedingScraping')
            ->once()
            ->andReturn(collect([$this->restaurant]));
            
        $this->menuScrapingService->shouldReceive('getScrapingStatusSummary')
            ->once()
            ->andReturn([
                'total_scrapes' => 10,
                'successful_scrapes' => 8,
                'failed_scrapes' => 2,
            ]);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.menu-scraping.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.menu-scraping.dashboard');
        $response->assertViewHas('restaurants');
        $response->assertViewHas('restaurantsNeedingScraping');
        $response->assertViewHas('scrapingSummary');
    }

    #[Test]
    public function non_admin_cannot_access_menu_scraping_dashboard()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.menu-scraping.dashboard'));
        
        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_scrape_single_restaurant()
    {
        $this->menuScrapingService->shouldReceive('scrapeRestaurant')
            ->once()
            ->withArgs(function ($restaurant, $forceUpdate) {
                return $restaurant->id === $this->restaurant->id && $forceUpdate === false;
            })
            ->andReturn([
                'status' => 'success',
                'message' => 'Menu scraped successfully',
                'items_found' => 10,
                'items_created' => 8,
                'items_updated' => 2,
            ]);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.menu-scraping.scrape-restaurant', $this->restaurant->id));
        
        $response->assertRedirect(route('admin.menu-scraping.history', $this->restaurant->id));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_force_scrape_restaurant()
    {
        $this->menuScrapingService->shouldReceive('scrapeRestaurant')
            ->once()
            ->withArgs(function ($restaurant, $forceUpdate) {
                return $restaurant->id === $this->restaurant->id && $forceUpdate === true;
            })
            ->andReturn([
                'status' => 'success',
                'message' => 'Menu scraped successfully',
                'items_found' => 10,
                'items_created' => 8,
                'items_updated' => 2,
            ]);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.menu-scraping.scrape-restaurant', $this->restaurant->id), [
                'force' => 'true',
            ]);
        
        $response->assertRedirect(route('admin.menu-scraping.history', $this->restaurant->id));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_scrape_multiple_restaurants()
    {
        $restaurant2 = Restaurant::factory()->create([
            'menu_url' => 'https://example2.com/menu',
            'menu_scraping_enabled' => true,
        ]);
        
        $this->menuScrapingService->shouldReceive('scrapeMultipleRestaurants')
            ->once()
            ->withArgs(function ($restaurantIds, $forceUpdate) use ($restaurant2) {
                return $restaurantIds == [$this->restaurant->id, $restaurant2->id] && $forceUpdate === false;
            })
            ->andReturn([
                $this->restaurant->id => [
                    'status' => 'success',
                    'message' => 'Menu scraped successfully',
                ],
                $restaurant2->id => [
                    'status' => 'success',
                    'message' => 'Menu scraped successfully',
                ],
            ]);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.menu-scraping.scrape-multiple'), [
                'restaurant_ids' => [$this->restaurant->id, $restaurant2->id],
            ]);
        
        $response->assertRedirect(route('admin.menu-scraping.dashboard'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_view_scraping_history()
    {
        $scrapingLogs = ScrapingLog::factory()->count(3)->create([
            'restaurant_id' => $this->restaurant->id,
        ]);
        
        $this->menuScrapingService->shouldReceive('getScrapingHistory')
            ->once()
            ->withArgs(function ($restaurant) {
                return $restaurant->id === $this->restaurant->id;
            })
            ->andReturn($scrapingLogs);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.menu-scraping.history', $this->restaurant->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.menu-scraping.history');
        $response->assertViewHas('restaurant');
        $response->assertViewHas('history');
    }

    #[Test]
    public function admin_can_edit_menu_url()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.menu-scraping.edit-menu-url', $this->restaurant->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.menu-scraping.edit-menu-url');
        $response->assertViewHas('restaurant');
    }

    #[Test]
    public function admin_can_update_menu_url()
    {
        $updatedData = [
            'menu_url' => 'https://example.com/updated-menu',
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'daily',
            'scraping_notes' => 'Test notes',
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.menu-scraping.update-menu-url', $this->restaurant->id), $updatedData);
        
        $response->assertRedirect(route('admin.menu-scraping.dashboard'));
        $response->assertSessionHas('success');
        
        $this->restaurant->refresh();
        $this->assertEquals($updatedData['menu_url'], $this->restaurant->menu_url);
        $this->assertEquals($updatedData['menu_scrape_frequency'], $this->restaurant->menu_scrape_frequency);
        $this->assertEquals($updatedData['scraping_notes'], $this->restaurant->scraping_notes);
        $this->assertTrue($this->restaurant->menu_scraping_enabled);
    }

    #[Test]
    public function validation_fails_with_invalid_menu_url()
    {
        $invalidData = [
            'menu_url' => 'not-a-valid-url',
            'menu_scraping_enabled' => true,
            'menu_scrape_frequency' => 'daily',
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.menu-scraping.update-menu-url', $this->restaurant->id), $invalidData);
        
        $response->assertRedirect();
        $response->assertSessionHasErrors('menu_url');
    }
}