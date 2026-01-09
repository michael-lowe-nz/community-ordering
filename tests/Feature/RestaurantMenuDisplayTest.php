<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantMenuDisplayTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function restaurant_with_no_menu_shows_appropriate_message()
    {
        // Create a restaurant with no menu
        $restaurant = Restaurant::factory()->create();

        // Visit the restaurant page
        $response = $this->get("/restaurant/{$restaurant->id}");

        // Assert the response contains the no menu message
        $response->assertStatus(200);
        $response->assertSee('No menu information is currently available for this restaurant');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function restaurant_with_menu_displays_items_organized_by_sections()
    {
        // Create a restaurant
        $restaurant = Restaurant::factory()->create();

        // Create an active menu for the restaurant
        $menu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Lunch Menu',
            'is_active' => true,
            'scraped_at' => now()->subDays(2),
        ]);

        // Create menu items with different sections
        $appetizerItems = MenuItem::factory()->count(2)->create([
            'menu_id' => $menu->id,
            'section' => 'Appetizers',
            'is_available' => true,
        ]);

        $mainCourseItems = MenuItem::factory()->count(3)->create([
            'menu_id' => $menu->id,
            'section' => 'Main Courses',
            'is_available' => true,
        ]);

        $dessertItems = MenuItem::factory()->count(1)->create([
            'menu_id' => $menu->id,
            'section' => 'Desserts',
            'is_available' => true,
        ]);

        // Visit the restaurant page
        $response = $this->get("/restaurant/{$restaurant->id}");

        // Assert the response contains the menu sections and items
        $response->assertStatus(200);
        $response->assertSee('Appetizers');
        $response->assertSee('Main Courses');
        $response->assertSee('Desserts');
        
        // Check for specific menu items
        foreach ($appetizerItems as $item) {
            $response->assertSee($item->name);
        }
        
        foreach ($mainCourseItems as $item) {
            $response->assertSee($item->name);
        }
        
        foreach ($dessertItems as $item) {
            $response->assertSee($item->name);
        }
        
        // Check for the last updated timestamp
        $response->assertSee($menu->scraped_at->format('F j, Y'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_menu_items_are_not_displayed()
    {
        // Create a restaurant
        $restaurant = Restaurant::factory()->create();

        // Create an active menu for the restaurant
        $menu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'is_active' => true,
        ]);

        // Create available menu items
        $availableItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Available Item',
            'is_available' => true,
        ]);

        // Create unavailable menu items
        $unavailableItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Unavailable Item',
            'is_available' => false,
        ]);

        // Visit the restaurant page
        $response = $this->get("/restaurant/{$restaurant->id}");

        // Assert the response contains only the available item
        $response->assertStatus(200);
        $response->assertSee('Available Item');
        $response->assertDontSee('Unavailable Item');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_menus_are_not_displayed()
    {
        // Create a restaurant
        $restaurant = Restaurant::factory()->create();

        // Create an active menu
        $activeMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Active Menu',
            'is_active' => true,
        ]);

        // Create menu items for the active menu
        MenuItem::factory()->create([
            'menu_id' => $activeMenu->id,
            'name' => 'Active Menu Item',
            'is_available' => true,
        ]);

        // Create an inactive menu
        $inactiveMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Inactive Menu',
            'is_active' => false,
        ]);

        // Create menu items for the inactive menu
        MenuItem::factory()->create([
            'menu_id' => $inactiveMenu->id,
            'name' => 'Inactive Menu Item',
            'is_available' => true,
        ]);

        // Visit the restaurant page
        $response = $this->get("/restaurant/{$restaurant->id}");

        // Assert the response contains only the active menu items
        $response->assertStatus(200);
        $response->assertSee('Active Menu Item');
        $response->assertDontSee('Inactive Menu Item');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_menus_are_displayed_with_their_names()
    {
        // Create a restaurant
        $restaurant = Restaurant::factory()->create();

        // Create two active menus
        $lunchMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Lunch Menu',
            'is_active' => true,
        ]);

        $dinnerMenu = Menu::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Dinner Menu',
            'is_active' => true,
        ]);

        // Create menu items for both menus
        MenuItem::factory()->create([
            'menu_id' => $lunchMenu->id,
            'name' => 'Lunch Special',
            'is_available' => true,
        ]);

        MenuItem::factory()->create([
            'menu_id' => $dinnerMenu->id,
            'name' => 'Dinner Special',
            'is_available' => true,
        ]);

        // Visit the restaurant page
        $response = $this->get("/restaurant/{$restaurant->id}");

        // Assert the response contains both menu names and their items
        $response->assertStatus(200);
        $response->assertSee('Lunch Menu');
        $response->assertSee('Dinner Menu');
        $response->assertSee('Lunch Special');
        $response->assertSee('Dinner Special');
    }
}