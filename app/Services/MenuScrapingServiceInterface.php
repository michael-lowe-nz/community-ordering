<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Support\Collection;

interface MenuScrapingServiceInterface
{
    /**
     * Scrape menu data for a single restaurant.
     *
     * @param Restaurant $restaurant
     * @param bool $forceUpdate Force update even if not due for scraping
     * @return array Scraping result with status and details
     */
    public function scrapeRestaurant(Restaurant $restaurant, bool $forceUpdate = false): array;

    /**
     * Scrape menu data for multiple restaurants.
     *
     * @param array $restaurantIds
     * @param bool $forceUpdate Force update even if not due for scraping
     * @return array Results for each restaurant
     */
    public function scrapeMultipleRestaurants(array $restaurantIds, bool $forceUpdate = false): array;

    /**
     * Get scraping history for a restaurant.
     *
     * @param Restaurant $restaurant
     * @param int $limit
     * @return Collection
     */
    public function getScrapingHistory(Restaurant $restaurant, int $limit = 10): Collection;

    /**
     * Get restaurants that need scraping based on their frequency settings.
     *
     * @param int $limit Maximum number of restaurants to return
     * @return Collection
     */
    public function getRestaurantsNeedingScraping(int $limit = 10): Collection;

    /**
     * Get scraping status summary.
     *
     * @param int $days Number of days to include in the summary
     * @return array
     */
    public function getScrapingStatusSummary(int $days = 7): array;
}