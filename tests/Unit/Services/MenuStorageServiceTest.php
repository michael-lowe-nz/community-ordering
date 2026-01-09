<?php

namespace Tests\Unit\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\MenuStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MenuStorageService $service;
    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MenuStorageService();
        $this->restaurant = Restaurant::factory()->create([
            'name' => 'Test Restaurant',
            'menu_scraping_enabled' => true,
        ]);
    }

    public function test_create_menu_with_default_values()
    {
        $menuData = [
            'name' => 'Lunch Menu',
            'source_url' => 'https://example.com/menu'
        ];

        $menu = $this->service->createMenu($this->restaurant, $menuData);

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals($this->restaurant->id, $menu->restaurant_id);
        $this->assertEquals('Lunch Menu', $menu->name);
        $this->assertEquals('scraped', $menu->menu_type);
        $this->assertTrue($menu->is_active);
        $this->assertEquals('https://example.com/menu', $menu->source_url);
        $this->assertNotNull($menu->scraped_at);
    }

    public function test_create_menu_with_custom_values()
    {
        $menuData = [
            'name' => 'Dinner Menu',
            'menu_type' => 'manual',
            'is_active' => false,
        ];

        $menu = $this->service->createMenu($this->restaurant, $menuData);

        $this->assertEquals('Dinner Menu', $menu->name);
        $this->assertEquals('manual', $menu->menu_type);
        $this->assertFalse($menu->is_active);
    }

    public function test_store_menu_items_creates_items_successfully()
    {
        $menu = Menu::factory()->create(['restaurant_id' => $this->restaurant->id]);
        
        $menuItems = [
            [
                'name' => 'Burger',
                'description' => 'Delicious burger',
                'price' => 12.99,
                'section' => 'Main Course'
            ],
            [
                'name' => 'Fries',
                'description' => 'Crispy fries',
                'price' => 4.99,
                'section' => 'Sides'
            ]
        ];

        $createdCount = $this->service->storeMenuItems($menu, $menuItems);

        $this->assertEquals(2, $createdCount);
        $this->assertEquals(2, $menu->menuItems()->count());
        
        $burger = $menu->menuItems()->where('name', 'Burger')->first();
        $this->assertNotNull($burger);
        $this->assertEquals('Delicious burger', $burger->description);
        $this->assertEquals(12.99, $burger->price);
        $this->assertEquals('Main Course', $burger->section);
        $this->assertEquals(0, $burger->order_index);
        $this->assertTrue($burger->is_available);
    }

    public function test_store_menu_items_handles_invalid_data()
    {
        $menu = Menu::factory()->create(['restaurant_id' => $this->restaurant->id]);
        
        $menuItems = [
            [
                'name' => 'Valid Item',
                'price' => 10.00
            ],
            [
                // Missing required name field
                'description' => 'Invalid item without name',
                'price' => 15.00
            ],
            [
                'name' => 'Another Valid Item',
                'price' => 8.50
            ]
        ];

        $createdCount = $this->service->storeMenuItems($menu, $menuItems);

        $this->assertEquals(2, $createdCount); // Only valid items created
        $this->assertEquals(2, $menu->menuItems()->count());
    }

    public function test_update_existing_items()
    {
        $menu = Menu::factory()->create(['restaurant_id' => $this->restaurant->id]);
        
        // Create existing items
        $existingItem1 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Burger',
            'price' => 10.00,
            'description' => 'Old description'
        ]);
        
        $existingItem2 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Pizza',
            'price' => 15.00
        ]);

        $updateData = [
            [
                'name' => 'Burger',
                'price' => 12.99,
                'description' => 'Updated burger description',
                'section' => 'Main Course'
            ],
            [
                'name' => 'Salad', // New item, won't update existing
                'price' => 8.99
            ]
        ];

        $updatedCount = $this->service->updateExistingItems($menu, $updateData);

        $this->assertEquals(1, $updatedCount);
        
        $existingItem1->refresh();
        $this->assertEquals(12.99, $existingItem1->price);
        $this->assertEquals('Updated burger description', $existingItem1->description);
        $this->assertEquals('Main Course', $existingItem1->section);
        
        // Pizza should remain unchanged
        $existingItem2->refresh();
        $this->assertEquals(15.00, $existingItem2->price);
    }

    public function test_deactivate_old_menus()
    {
        // Create multiple menus for the restaurant
        $oldMenu1 = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true,
            'name' => 'Old Menu 1'
        ]);
        
        $oldMenu2 = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true,
            'name' => 'Old Menu 2'
        ]);
        
        $currentMenu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true,
            'name' => 'Current Menu'
        ]);

        $this->service->deactivateOldMenus($this->restaurant, $currentMenu);

        $oldMenu1->refresh();
        $oldMenu2->refresh();
        $currentMenu->refresh();

        $this->assertFalse($oldMenu1->is_active);
        $this->assertFalse($oldMenu2->is_active);
        $this->assertTrue($currentMenu->is_active);
    }

    public function test_find_similar_items_with_exact_match()
    {
        $activeMenu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $activeMenu->id,
            'name' => 'Chicken Burger'
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $activeMenu->id,
            'name' => 'Beef Burger'
        ]);

        $similarItems = $this->service->findSimilarItems($this->restaurant, 'Chicken Burger');

        $this->assertEquals(1, $similarItems->count());
        $this->assertEquals('Chicken Burger', $similarItems->first()->name);
    }

    public function test_find_similar_items_with_fuzzy_match()
    {
        $activeMenu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $activeMenu->id,
            'name' => 'Chicken Burger Deluxe'
        ]);

        $similarItems = $this->service->findSimilarItems($this->restaurant, 'Chicken Burger');

        $this->assertEquals(1, $similarItems->count());
        $this->assertEquals('Chicken Burger Deluxe', $similarItems->first()->name);
    }

    public function test_find_similar_items_ignores_inactive_menus()
    {
        $inactiveMenu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => false
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $inactiveMenu->id,
            'name' => 'Chicken Burger'
        ]);

        $similarItems = $this->service->findSimilarItems($this->restaurant, 'Chicken Burger');

        $this->assertEquals(0, $similarItems->count());
    }

    public function test_process_menu_data_complete_workflow()
    {
        // Create an existing active menu with items
        $oldMenu = Menu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'is_active' => true,
            'name' => 'Old Menu'
        ]);
        
        MenuItem::factory()->create([
            'menu_id' => $oldMenu->id,
            'name' => 'Burger',
            'price' => 10.00
        ]);

        $menuData = [
            'name' => 'New Menu',
            'source_url' => 'https://example.com/new-menu'
        ];

        $menuItems = [
            [
                'name' => 'Burger', // Similar to existing item
                'price' => 12.99,
                'description' => 'Updated burger'
            ],
            [
                'name' => 'New Pizza', // Completely new item
                'price' => 15.99,
                'description' => 'Fresh pizza'
            ]
        ];

        $result = $this->service->processMenuData($this->restaurant, $menuData, $menuItems);

        // Verify the result structure
        $this->assertArrayHasKey('menu', $result);
        $this->assertArrayHasKey('items_created', $result);
        $this->assertArrayHasKey('items_updated', $result);
        $this->assertArrayHasKey('total_items', $result);

        // Verify new menu was created
        $newMenu = $result['menu'];
        $this->assertInstanceOf(Menu::class, $newMenu);
        $this->assertEquals('New Menu', $newMenu->name);
        $this->assertTrue($newMenu->is_active);

        // Verify items were created
        $this->assertEquals(2, $result['total_items']);
        $this->assertEquals(2, $newMenu->menuItems()->count());

        // Verify old menu was deactivated
        $oldMenu->refresh();
        $this->assertFalse($oldMenu->is_active);
    }

    public function test_process_menu_data_handles_transaction_rollback()
    {
        // Force a database error to test transaction rollback
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $menuData = ['name' => 'Test Menu'];
        $menuItems = [['name' => 'Test Item']];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->processMenuData($this->restaurant, $menuData, $menuItems);
    }

    public function test_normalize_item_name()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeItemName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Chicken Burger (Deluxe)!');
        $this->assertEquals('chicken burger deluxe', $result);

        $result = $method->invoke($this->service, '  PIZZA MARGHERITA  ');
        $this->assertEquals('pizza margherita', $result);
    }

    public function test_calculate_similarity()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateSimilarity');
        $method->setAccessible(true);

        // Exact match
        $similarity = $method->invoke($this->service, 'burger', 'burger');
        $this->assertEquals(1.0, $similarity);

        // Completely different
        $similarity = $method->invoke($this->service, 'burger', 'pizza');
        $this->assertLessThan(0.5, $similarity);

        // Similar strings
        $similarity = $method->invoke($this->service, 'chicken burger', 'chicken burgers');
        $this->assertGreaterThan(0.8, $similarity);

        // Empty strings
        $similarity = $method->invoke($this->service, '', 'burger');
        $this->assertEquals(0.0, $similarity);
    }
}