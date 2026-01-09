<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FictionalRestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating fictional restaurants with menus...');

        // Define cuisine types with corresponding menu sections and items
        $cuisineTypes = [
            'Italian' => [
                'sections' => ['Antipasti', 'Pasta', 'Pizza', 'Secondi', 'Dolci', 'Bevande'],
                'items' => [
                    'Antipasti' => [
                        ['name' => 'Bruschetta', 'description' => 'Toasted bread topped with fresh tomatoes, garlic, and basil', 'price' => 8.95],
                        ['name' => 'Caprese Salad', 'description' => 'Fresh mozzarella, tomatoes, and basil drizzled with balsamic glaze', 'price' => 10.95],
                        ['name' => 'Calamari Fritti', 'description' => 'Crispy fried calamari served with marinara sauce', 'price' => 12.95],
                        ['name' => 'Arancini', 'description' => 'Fried rice balls stuffed with mozzarella and peas', 'price' => 9.95],
                    ],
                    'Pasta' => [
                        ['name' => 'Spaghetti Carbonara', 'description' => 'Classic pasta with pancetta, egg, and pecorino cheese', 'price' => 16.95],
                        ['name' => 'Fettuccine Alfredo', 'description' => 'Creamy parmesan sauce with fettuccine pasta', 'price' => 15.95],
                        ['name' => 'Lasagna', 'description' => 'Layers of pasta, meat sauce, and cheese baked to perfection', 'price' => 18.95],
                        ['name' => 'Penne Arrabbiata', 'description' => 'Spicy tomato sauce with garlic and red chili', 'price' => 14.95],
                    ],
                    'Pizza' => [
                        ['name' => 'Margherita', 'description' => 'Classic pizza with tomato sauce, mozzarella, and basil', 'price' => 14.95],
                        ['name' => 'Quattro Formaggi', 'description' => 'Four cheese pizza with mozzarella, gorgonzola, fontina, and parmesan', 'price' => 16.95],
                        ['name' => 'Diavola', 'description' => 'Spicy salami, tomato sauce, and mozzarella', 'price' => 17.95],
                        ['name' => 'Prosciutto e Funghi', 'description' => 'Ham, mushrooms, tomato sauce, and mozzarella', 'price' => 18.95],
                    ],
                    'Secondi' => [
                        ['name' => 'Chicken Parmigiana', 'description' => 'Breaded chicken breast topped with marinara and melted mozzarella', 'price' => 19.95],
                        ['name' => 'Veal Saltimbocca', 'description' => 'Veal cutlets with prosciutto and sage in a white wine sauce', 'price' => 24.95],
                        ['name' => 'Osso Buco', 'description' => 'Braised veal shanks with vegetables and gremolata', 'price' => 28.95],
                    ],
                    'Dolci' => [
                        ['name' => 'Tiramisu', 'description' => 'Classic Italian dessert with coffee-soaked ladyfingers and mascarpone', 'price' => 8.95],
                        ['name' => 'Panna Cotta', 'description' => 'Vanilla cream dessert with berry compote', 'price' => 7.95],
                        ['name' => 'Cannoli', 'description' => 'Crispy pastry shells filled with sweet ricotta cream', 'price' => 6.95],
                    ],
                    'Bevande' => [
                        ['name' => 'Espresso', 'description' => 'Strong Italian coffee', 'price' => 3.50],
                        ['name' => 'Cappuccino', 'description' => 'Espresso with steamed milk and foam', 'price' => 4.50],
                        ['name' => 'Italian Soda', 'description' => 'Sparkling water with your choice of syrup', 'price' => 3.95],
                        ['name' => 'House Red Wine', 'description' => 'Glass of our house Chianti', 'price' => 7.95],
                    ],
                ],
            ],
            'Japanese' => [
                'sections' => ['Appetizers', 'Sushi & Sashimi', 'Ramen', 'Main Dishes', 'Desserts', 'Drinks'],
                'items' => [
                    'Appetizers' => [
                        ['name' => 'Edamame', 'description' => 'Steamed soybeans with sea salt', 'price' => 5.95],
                        ['name' => 'Gyoza', 'description' => 'Pan-fried pork dumplings', 'price' => 7.95],
                        ['name' => 'Agedashi Tofu', 'description' => 'Fried tofu in dashi broth', 'price' => 6.95],
                        ['name' => 'Tempura', 'description' => 'Lightly battered and fried shrimp and vegetables', 'price' => 9.95],
                    ],
                    'Sushi & Sashimi' => [
                        ['name' => 'California Roll', 'description' => 'Crab, avocado, and cucumber', 'price' => 8.95],
                        ['name' => 'Spicy Tuna Roll', 'description' => 'Fresh tuna with spicy mayo', 'price' => 9.95],
                        ['name' => 'Salmon Nigiri', 'description' => 'Fresh salmon over pressed rice (2 pieces)', 'price' => 6.95],
                        ['name' => 'Sashimi Platter', 'description' => 'Assortment of fresh raw fish (12 pieces)', 'price' => 24.95],
                    ],
                    'Ramen' => [
                        ['name' => 'Tonkotsu Ramen', 'description' => 'Rich pork broth with chashu, egg, and vegetables', 'price' => 14.95],
                        ['name' => 'Miso Ramen', 'description' => 'Miso-based broth with corn, butter, and ground pork', 'price' => 13.95],
                        ['name' => 'Shoyu Ramen', 'description' => 'Soy sauce-based broth with chicken, egg, and green onions', 'price' => 13.95],
                    ],
                    'Main Dishes' => [
                        ['name' => 'Chicken Teriyaki', 'description' => 'Grilled chicken with teriyaki sauce and steamed rice', 'price' => 16.95],
                        ['name' => 'Katsu Curry', 'description' => 'Breaded pork cutlet with Japanese curry and rice', 'price' => 17.95],
                        ['name' => 'Unagi Don', 'description' => 'Grilled eel over rice with sweet sauce', 'price' => 19.95],
                    ],
                    'Desserts' => [
                        ['name' => 'Mochi Ice Cream', 'description' => 'Rice cake filled with ice cream (3 pieces)', 'price' => 6.95],
                        ['name' => 'Green Tea Tiramisu', 'description' => 'Japanese twist on the classic Italian dessert', 'price' => 7.95],
                    ],
                    'Drinks' => [
                        ['name' => 'Green Tea', 'description' => 'Traditional Japanese green tea', 'price' => 3.50],
                        ['name' => 'Ramune', 'description' => 'Japanese soda with marble', 'price' => 4.50],
                        ['name' => 'Sake', 'description' => 'Traditional Japanese rice wine (small)', 'price' => 8.95],
                    ],
                ],
            ],
            'Mexican' => [
                'sections' => ['Appetizers', 'Tacos', 'Burritos & Enchiladas', 'Specialties', 'Sides', 'Desserts', 'Beverages'],
                'items' => [
                    'Appetizers' => [
                        ['name' => 'Guacamole & Chips', 'description' => 'Fresh avocado dip with homemade tortilla chips', 'price' => 8.95],
                        ['name' => 'Queso Fundido', 'description' => 'Melted cheese with chorizo and flour tortillas', 'price' => 9.95],
                        ['name' => 'Nachos Supreme', 'description' => 'Tortilla chips topped with beans, cheese, jalapeños, and sour cream', 'price' => 10.95],
                    ],
                    'Tacos' => [
                        ['name' => 'Carne Asada Tacos', 'description' => 'Grilled steak tacos with onions and cilantro (3)', 'price' => 12.95],
                        ['name' => 'Fish Tacos', 'description' => 'Battered fish with cabbage slaw and chipotle mayo (3)', 'price' => 13.95],
                        ['name' => 'Al Pastor Tacos', 'description' => 'Marinated pork with pineapple, onions, and cilantro (3)', 'price' => 11.95],
                    ],
                    'Burritos & Enchiladas' => [
                        ['name' => 'Carne Asada Burrito', 'description' => 'Grilled steak with rice, beans, and cheese', 'price' => 14.95],
                        ['name' => 'Veggie Burrito', 'description' => 'Grilled vegetables, rice, beans, and guacamole', 'price' => 12.95],
                        ['name' => 'Chicken Enchiladas', 'description' => 'Corn tortillas filled with chicken and topped with red sauce (3)', 'price' => 15.95],
                    ],
                    'Specialties' => [
                        ['name' => 'Chiles Rellenos', 'description' => 'Stuffed poblano peppers with cheese, battered and fried', 'price' => 16.95],
                        ['name' => 'Fajitas', 'description' => 'Sizzling plate with your choice of protein and vegetables', 'price' => 18.95],
                        ['name' => 'Mole Poblano', 'description' => 'Chicken in rich mole sauce served with rice', 'price' => 17.95],
                    ],
                    'Sides' => [
                        ['name' => 'Mexican Rice', 'description' => 'Traditional tomato-infused rice', 'price' => 3.95],
                        ['name' => 'Refried Beans', 'description' => 'Creamy pinto beans with cheese', 'price' => 3.95],
                        ['name' => 'Elote', 'description' => 'Mexican street corn with mayo, cheese, and chili powder', 'price' => 4.95],
                    ],
                    'Desserts' => [
                        ['name' => 'Churros', 'description' => 'Fried dough pastry with cinnamon sugar and chocolate sauce', 'price' => 6.95],
                        ['name' => 'Flan', 'description' => 'Traditional Mexican caramel custard', 'price' => 5.95],
                    ],
                    'Beverages' => [
                        ['name' => 'Horchata', 'description' => 'Sweet rice milk with cinnamon', 'price' => 3.95],
                        ['name' => 'Agua Fresca', 'description' => 'Fresh fruit water (ask for today\'s flavor)', 'price' => 3.95],
                        ['name' => 'Margarita', 'description' => 'Classic lime margarita with salt rim', 'price' => 8.95],
                    ],
                ],
            ],
            'Indian' => [
                'sections' => ['Starters', 'Tandoori', 'Curries', 'Vegetarian', 'Breads', 'Rice', 'Desserts', 'Drinks'],
                'items' => [
                    'Starters' => [
                        ['name' => 'Vegetable Samosas', 'description' => 'Crispy pastry filled with spiced potatoes and peas (2)', 'price' => 5.95],
                        ['name' => 'Onion Bhaji', 'description' => 'Crispy onion fritters with chickpea batter', 'price' => 6.95],
                        ['name' => 'Chicken Pakora', 'description' => 'Spiced chicken fritters', 'price' => 7.95],
                    ],
                    'Tandoori' => [
                        ['name' => 'Tandoori Chicken', 'description' => 'Yogurt and spice marinated chicken cooked in clay oven', 'price' => 15.95],
                        ['name' => 'Seekh Kebab', 'description' => 'Minced lamb skewers with herbs and spices', 'price' => 16.95],
                        ['name' => 'Paneer Tikka', 'description' => 'Marinated and grilled Indian cheese with vegetables', 'price' => 14.95],
                    ],
                    'Curries' => [
                        ['name' => 'Butter Chicken', 'description' => 'Tender chicken in a rich tomato and butter sauce', 'price' => 16.95],
                        ['name' => 'Lamb Rogan Josh', 'description' => 'Slow-cooked lamb in aromatic Kashmiri spices', 'price' => 18.95],
                        ['name' => 'Prawn Vindaloo', 'description' => 'Spicy curry with prawns, potatoes, and vinegar', 'price' => 19.95],
                    ],
                    'Vegetarian' => [
                        ['name' => 'Palak Paneer', 'description' => 'Spinach curry with Indian cheese', 'price' => 14.95],
                        ['name' => 'Chana Masala', 'description' => 'Spiced chickpeas in tomato sauce', 'price' => 13.95],
                        ['name' => 'Dal Makhani', 'description' => 'Creamy black lentils cooked with butter and cream', 'price' => 12.95],
                    ],
                    'Breads' => [
                        ['name' => 'Naan', 'description' => 'Traditional leavened flatbread', 'price' => 3.50],
                        ['name' => 'Garlic Naan', 'description' => 'Flatbread with garlic and herbs', 'price' => 4.50],
                        ['name' => 'Paratha', 'description' => 'Flaky layered whole wheat bread', 'price' => 4.95],
                    ],
                    'Rice' => [
                        ['name' => 'Basmati Rice', 'description' => 'Aromatic long-grain rice', 'price' => 3.95],
                        ['name' => 'Vegetable Biryani', 'description' => 'Fragrant rice cooked with vegetables and spices', 'price' => 14.95],
                        ['name' => 'Chicken Biryani', 'description' => 'Fragrant rice cooked with chicken and spices', 'price' => 16.95],
                    ],
                    'Desserts' => [
                        ['name' => 'Gulab Jamun', 'description' => 'Sweet milk dumplings in rose syrup', 'price' => 5.95],
                        ['name' => 'Kheer', 'description' => 'Rice pudding with cardamom and nuts', 'price' => 5.95],
                    ],
                    'Drinks' => [
                        ['name' => 'Mango Lassi', 'description' => 'Yogurt drink with mango', 'price' => 4.95],
                        ['name' => 'Masala Chai', 'description' => 'Spiced Indian tea with milk', 'price' => 3.95],
                    ],
                ],
            ],
            'Thai' => [
                'sections' => ['Appetizers', 'Soups', 'Salads', 'Curries', 'Noodles & Rice', 'Specialties', 'Desserts', 'Beverages'],
                'items' => [
                    'Appetizers' => [
                        ['name' => 'Spring Rolls', 'description' => 'Crispy rolls with vegetables and glass noodles (4)', 'price' => 6.95],
                        ['name' => 'Satay', 'description' => 'Grilled chicken skewers with peanut sauce (4)', 'price' => 8.95],
                        ['name' => 'Crab Rangoon', 'description' => 'Crispy wontons filled with cream cheese and crab (6)', 'price' => 7.95],
                    ],
                    'Soups' => [
                        ['name' => 'Tom Yum Goong', 'description' => 'Spicy and sour soup with shrimp and mushrooms', 'price' => 7.95],
                        ['name' => 'Tom Kha Gai', 'description' => 'Coconut soup with chicken, galangal, and lime', 'price' => 6.95],
                    ],
                    'Salads' => [
                        ['name' => 'Papaya Salad', 'description' => 'Shredded green papaya with tomatoes, peanuts, and lime dressing', 'price' => 9.95],
                        ['name' => 'Beef Salad', 'description' => 'Grilled beef with cucumber, tomatoes, and spicy dressing', 'price' => 12.95],
                    ],
                    'Curries' => [
                        ['name' => 'Green Curry', 'description' => 'Spicy curry with coconut milk, bamboo shoots, and basil', 'price' => 15.95],
                        ['name' => 'Panang Curry', 'description' => 'Rich curry with coconut milk and lime leaves', 'price' => 16.95],
                        ['name' => 'Massaman Curry', 'description' => 'Mild curry with potatoes, onions, and peanuts', 'price' => 16.95],
                    ],
                    'Noodles & Rice' => [
                        ['name' => 'Pad Thai', 'description' => 'Stir-fried rice noodles with egg, tofu, bean sprouts, and peanuts', 'price' => 14.95],
                        ['name' => 'Pad See Ew', 'description' => 'Wide rice noodles stir-fried with egg, broccoli, and sweet soy sauce', 'price' => 14.95],
                        ['name' => 'Pineapple Fried Rice', 'description' => 'Fried rice with pineapple, cashews, and curry powder', 'price' => 15.95],
                    ],
                    'Specialties' => [
                        ['name' => 'Basil Stir Fry', 'description' => 'Spicy stir-fry with basil, chili, and garlic', 'price' => 16.95],
                        ['name' => 'Ginger Fish', 'description' => 'Steamed fish with ginger, mushrooms, and scallions', 'price' => 19.95],
                    ],
                    'Desserts' => [
                        ['name' => 'Mango Sticky Rice', 'description' => 'Sweet sticky rice with fresh mango and coconut cream', 'price' => 7.95],
                        ['name' => 'Fried Banana', 'description' => 'Banana wrapped in spring roll wrapper and fried, served with ice cream', 'price' => 6.95],
                    ],
                    'Beverages' => [
                        ['name' => 'Thai Iced Tea', 'description' => 'Sweet tea with condensed milk', 'price' => 3.95],
                        ['name' => 'Thai Iced Coffee', 'description' => 'Strong coffee with condensed milk', 'price' => 3.95],
                    ],
                ],
            ],
        ];

        // Create fictional restaurants for each cuisine type
        foreach ($cuisineTypes as $cuisineType => $menuData) {
            // Create 2 restaurants for each cuisine type
            for ($i = 1; $i <= 2; $i++) {
                $restaurantName = $this->getRestaurantName($cuisineType);
                
                // Create restaurant
                $restaurant = Restaurant::factory()->create([
                    'name' => $restaurantName,
                    'cuisine_type' => $cuisineType,
                    'price_range' => $this->getRandomPriceRange(),
                    'menu_scraping_enabled' => true,
                    'menu_url' => 'https://example.com/' . strtolower(str_replace(' ', '-', $restaurantName)) . '/menu',
                    'last_menu_scrape' => now()->subDays(rand(1, 14)),
                    'menu_scrape_frequency' => $this->getRandomFrequency(),
                    'rating' => $this->getRandomRating(),
                ]);
                
                $this->command->info("Created restaurant: {$restaurant->name}");
                
                // Create menus for the restaurant
                $menuTypes = ['Lunch', 'Dinner', 'Drinks'];
                $activeMenuTypes = array_slice($menuTypes, 0, rand(1, 3)); // Randomly select 1-3 menu types
                
                foreach ($activeMenuTypes as $menuType) {
                    $menu = Menu::factory()->create([
                        'restaurant_id' => $restaurant->id,
                        'name' => $menuType . ' Menu',
                        'menu_type' => 'scraped',
                        'is_active' => true,
                        'scraped_at' => now()->subDays(rand(1, 14)),
                        'source_url' => $restaurant->menu_url,
                    ]);
                    
                    $this->command->info("  - Created menu: {$menu->name}");
                    
                    // Add menu items based on cuisine type and menu type
                    $this->createMenuItems($menu, $menuData, $menuType);
                }
            }
        }
        
        $this->command->info('Fictional restaurants seeding completed!');
    }
    
    /**
     * Generate a random restaurant name based on cuisine type.
     */
    private function getRestaurantName(string $cuisineType): string
    {
        $prefixes = [
            'Italian' => ['La', 'Il', 'Bella', 'Piccolo', 'Casa', 'Trattoria', 'Osteria', 'Ristorante'],
            'Japanese' => ['Sakura', 'Tokyo', 'Fuji', 'Hana', 'Kiku', 'Matsu', 'Yama'],
            'Mexican' => ['El', 'La', 'Casa', 'Cantina', 'Taqueria', 'Fiesta'],
            'Indian' => ['Taj', 'Spice', 'Royal', 'Curry', 'Bombay', 'Delhi'],
            'Thai' => ['Bangkok', 'Siam', 'Thai', 'Lotus', 'Basil', 'Lemongrass'],
        ];
        
        $suffixes = [
            'Italian' => ['Trattoria', 'Cucina', 'Ristorante', 'Pizzeria', 'Pasta', 'Giardino', 'Taverna'],
            'Japanese' => ['Sushi', 'Ramen', 'Kitchen', 'House', 'Garden', 'Express', 'Bistro'],
            'Mexican' => ['Cantina', 'Taqueria', 'Grill', 'Cocina', 'Comida', 'Casa'],
            'Indian' => ['Palace', 'Garden', 'Spice', 'Tandoor', 'Kitchen', 'House', 'Bistro'],
            'Thai' => ['Kitchen', 'Garden', 'House', 'Bistro', 'Palace', 'Cafe'],
        ];
        
        $nouns = [
            'Italian' => ['Roma', 'Napoli', 'Milano', 'Venezia', 'Toscana', 'Sicilia', 'Amore', 'Gusto'],
            'Japanese' => ['Tokyo', 'Kyoto', 'Osaka', 'Nori', 'Zen', 'Umami', 'Kobe'],
            'Mexican' => ['Jalisco', 'Oaxaca', 'Guadalajara', 'Amigo', 'Sombrero', 'Fiesta'],
            'Indian' => ['Taj', 'Maharaja', 'Darbar', 'Masala', 'Ginger', 'Saffron'],
            'Thai' => ['Bangkok', 'Phuket', 'Chiang Mai', 'Orchid', 'Elephant', 'Jasmine'],
        ];
        
        $prefix = $prefixes[$cuisineType][array_rand($prefixes[$cuisineType])];
        $suffix = $suffixes[$cuisineType][array_rand($suffixes[$cuisineType])];
        $noun = $nouns[$cuisineType][array_rand($nouns[$cuisineType])];
        
        // 50% chance to use prefix + noun + suffix format, otherwise use prefix + noun or noun + suffix
        if (rand(0, 1) === 0) {
            return "{$prefix} {$noun}";
        } else {
            return "{$noun} {$suffix}";
        }
    }
    
    /**
     * Get a random price range.
     */
    private function getRandomPriceRange(): string
    {
        $ranges = ['$', '$$', '$$$', '$$$$'];
        $weights = [10, 50, 30, 10]; // Weighted probability
        
        return $this->weightedRandom($ranges, $weights);
    }
    
    /**
     * Get a random scraping frequency.
     */
    private function getRandomFrequency(): string
    {
        $frequencies = ['daily', 'weekly', 'monthly'];
        $weights = [20, 50, 30]; // Weighted probability
        
        return $this->weightedRandom($frequencies, $weights);
    }
    
    /**
     * Get a random rating between 3.0 and 5.0.
     */
    private function getRandomRating(): float
    {
        return round(mt_rand(30, 50) / 10, 1);
    }
    
    /**
     * Create menu items for a menu based on cuisine type and menu type.
     */
    private function createMenuItems(Menu $menu, array $menuData, string $menuType): void
    {
        $sections = $menuData['sections'];
        $items = $menuData['items'];
        
        // Select appropriate sections based on menu type
        $selectedSections = [];
        
        switch ($menuType) {
            case 'Lunch':
                // For lunch, include appetizers, main dishes, and some sides
                $selectedSections = array_filter($sections, function($section) {
                    return !in_array($section, ['Desserts', 'Drinks', 'Beverages']);
                });
                break;
                
            case 'Dinner':
                // For dinner, include all food sections
                $selectedSections = array_filter($sections, function($section) {
                    return !in_array($section, ['Drinks', 'Beverages']);
                });
                break;
                
            case 'Drinks':
                // For drinks, only include beverages and desserts
                $selectedSections = array_filter($sections, function($section) {
                    return in_array($section, ['Drinks', 'Beverages', 'Desserts', 'Dolci']);
                });
                break;
        }
        
        // If no sections were selected, use all sections
        if (empty($selectedSections)) {
            $selectedSections = $sections;
        }
        
        $orderIndex = 1;
        
        // Create menu items for each selected section
        foreach ($selectedSections as $section) {
            // Skip if section doesn't exist in items
            if (!isset($items[$section])) {
                continue;
            }
            
            $sectionItems = $items[$section];
            
            // For each item in the section
            foreach ($sectionItems as $itemData) {
                // 90% chance the item is available
                $isAvailable = (rand(1, 10) <= 9);
                
                MenuItem::factory()->create([
                    'menu_id' => $menu->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'price' => $itemData['price'],
                    'section' => $section,
                    'order_index' => $orderIndex++,
                    'is_available' => $isAvailable,
                ]);
            }
        }
        
        $this->command->info("    - Added " . count($menu->menuItems) . " menu items");
    }
    
    /**
     * Get a random value based on weights.
     */
    private function weightedRandom(array $values, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $rand = mt_rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($values as $index => $value) {
            $currentWeight += $weights[$index];
            if ($rand <= $currentWeight) {
                return $value;
            }
        }
        
        return $values[0]; // Fallback
    }
}