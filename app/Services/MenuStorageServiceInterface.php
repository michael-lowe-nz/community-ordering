<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Support\Collection;

interface MenuStorageServiceInterface
{
    /**
     * Create a new menu for a restaurant with proper versioning.
     */
    public function createMenu(Restaurant $restaurant, array $menuData): Menu;

    /**
     * Store menu items for a given menu.
     */
    public function storeMenuItems(Menu $menu, array $menuItems): int;

    /**
     * Update existing menu items with new data.
     */
    public function updateExistingItems(Menu $menu, array $menuItems): int;

    /**
     * Deactivate old menus when a new version is created.
     */
    public function deactivateOldMenus(Restaurant $restaurant, Menu $currentMenu): void;

    /**
     * Find similar menu items for duplicate detection.
     */
    public function findSimilarItems(Restaurant $restaurant, string $itemName): Collection;

    /**
     * Process menu data with duplicate detection and updates.
     */
    public function processMenuData(Restaurant $restaurant, array $menuData, array $menuItems): array;
}