<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenuStorageService implements MenuStorageServiceInterface
{
    /**
     * Create a new menu for a restaurant with proper versioning.
     */
    public function createMenu(Restaurant $restaurant, array $menuData): Menu
    {
        $menuData = array_merge([
            'restaurant_id' => $restaurant->id,
            'name' => $menuData['name'] ?? 'Main Menu',
            'menu_type' => $menuData['menu_type'] ?? 'scraped',
            'is_active' => true,
            'scraped_at' => now(),
            'source_url' => $menuData['source_url'] ?? null,
        ], $menuData);

        // Validate menu data
        $validated = validator($menuData, Menu::validationRules())->validate();

        return Menu::create($validated);
    }

    /**
     * Store menu items for a given menu.
     */
    public function storeMenuItems(Menu $menu, array $menuItems): int
    {
        $createdCount = 0;
        
        foreach ($menuItems as $index => $itemData) {
            $itemData = array_merge([
                'menu_id' => $menu->id,
                'order_index' => $itemData['order_index'] ?? $index,
                'is_available' => true,
            ], $itemData);

            // Validate menu item data
            try {
                $validated = validator($itemData, MenuItem::validationRules())->validate();
                MenuItem::create($validated);
                $createdCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to create menu item', [
                    'menu_id' => $menu->id,
                    'item_data' => $itemData,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $createdCount;
    }

    /**
     * Update existing menu items with new data.
     */
    public function updateExistingItems(Menu $menu, array $menuItems): int
    {
        $updatedCount = 0;
        
        foreach ($menuItems as $index => $itemData) {
            if (!isset($itemData['name'])) {
                continue;
            }

            // Find existing item by name within the same menu
            $existingItem = $menu->menuItems()
                ->where('name', $itemData['name'])
                ->first();

            if ($existingItem) {
                $updateData = array_merge([
                    'order_index' => $itemData['order_index'] ?? $index,
                    'is_available' => true,
                ], array_filter($itemData, function($value) {
                    return !is_null($value);
                }));

                // Remove menu_id from update data to prevent conflicts
                unset($updateData['menu_id']);

                try {
                    $validated = validator($updateData, array_intersect_key(
                        MenuItem::validationRules(),
                        $updateData
                    ))->validate();
                    
                    $existingItem->update($validated);
                    $updatedCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to update menu item', [
                        'item_id' => $existingItem->id,
                        'update_data' => $updateData,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $updatedCount;
    }

    /**
     * Deactivate old menus when a new version is created.
     */
    public function deactivateOldMenus(Restaurant $restaurant, Menu $currentMenu): void
    {
        $restaurant->menus()
            ->where('id', '!=', $currentMenu->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        Log::info('Deactivated old menus for restaurant', [
            'restaurant_id' => $restaurant->id,
            'current_menu_id' => $currentMenu->id
        ]);
    }

    /**
     * Find similar menu items for duplicate detection.
     */
    public function findSimilarItems(Restaurant $restaurant, string $itemName): Collection
    {
        // Get all menu items from active menus for this restaurant
        $menuIds = $restaurant->activeMenus()->pluck('id');
        
        if ($menuIds->isEmpty()) {
            return collect();
        }

        // Find items with similar names using fuzzy matching
        $normalizedName = $this->normalizeItemName($itemName);
        
        return MenuItem::whereIn('menu_id', $menuIds)
            ->get()
            ->filter(function ($item) use ($normalizedName) {
                $itemNormalized = $this->normalizeItemName($item->name);
                $similarity = $this->calculateSimilarity($normalizedName, $itemNormalized);
                
                // Use a lower threshold for fuzzy matching and also check if one contains the other
                return $similarity > 0.7 || 
                       str_contains($itemNormalized, $normalizedName) || 
                       str_contains($normalizedName, $itemNormalized);
            });
    }

    /**
     * Process menu data with duplicate detection and updates.
     */
    public function processMenuData(Restaurant $restaurant, array $menuData, array $menuItems): array
    {
        return DB::transaction(function () use ($restaurant, $menuData, $menuItems) {
            // Create new menu
            $menu = $this->createMenu($restaurant, $menuData);
            
            // Separate new items from potential updates
            $newItems = [];
            $updateItems = [];
            
            foreach ($menuItems as $index => $itemData) {
                if (!isset($itemData['name'])) {
                    continue;
                }
                
                $similarItems = $this->findSimilarItems($restaurant, $itemData['name']);
                
                if ($similarItems->isNotEmpty()) {
                    // This might be an update to an existing item
                    $updateItems[] = array_merge($itemData, ['order_index' => $index]);
                } else {
                    // This is a new item
                    $newItems[] = array_merge($itemData, ['order_index' => $index]);
                }
            }
            
            // Store new items
            $createdCount = $this->storeMenuItems($menu, $newItems);
            
            // Update existing items (create new versions in the new menu)
            $updatedCount = $this->storeMenuItems($menu, $updateItems);
            
            // Deactivate old menus
            $this->deactivateOldMenus($restaurant, $menu);
            
            return [
                'menu' => $menu,
                'items_created' => $createdCount,
                'items_updated' => $updatedCount,
                'total_items' => $createdCount + $updatedCount
            ];
        });
    }

    /**
     * Normalize item name for comparison.
     */
    private function normalizeItemName(string $name): string
    {
        return Str::lower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name)));
    }

    /**
     * Calculate similarity between two strings.
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        // Use Levenshtein distance for similarity calculation
        $maxLength = max(strlen($str1), strlen($str2));
        if ($maxLength === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLength);
    }
}