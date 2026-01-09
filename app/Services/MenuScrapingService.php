<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use App\Models\Menu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;

class MenuScrapingService implements MenuScrapingServiceInterface
{
    protected WebScrapingServiceInterface $webScrapingService;
    protected MenuParsingServiceInterface $menuParsingService;
    protected MenuStorageServiceInterface $menuStorageService;
    protected ScrapingLogServiceInterface $scrapingLogService;
    protected MenuContentValidationService $menuContentValidationService;
    
    /**
     * Minimum time between scraping requests in seconds
     */
    protected int $rateLimitDelay = 2;

    /**
     * Constructor
     */
    public function __construct(
        WebScrapingServiceInterface $webScrapingService,
        MenuParsingServiceInterface $menuParsingService,
        MenuStorageServiceInterface $menuStorageService,
        ScrapingLogServiceInterface $scrapingLogService,
        MenuContentValidationService $menuContentValidationService
    ) {
        $this->webScrapingService = $webScrapingService;
        $this->menuParsingService = $menuParsingService;
        $this->menuStorageService = $menuStorageService;
        $this->scrapingLogService = $scrapingLogService;
        $this->menuContentValidationService = $menuContentValidationService;
    }

    /**
     * Scrape menu data for a single restaurant.
     *
     * @param Restaurant $restaurant
     * @param bool $forceUpdate Force update even if not due for scraping
     * @return array Scraping result with status and details
     */
    public function scrapeRestaurant(Restaurant $restaurant, bool $forceUpdate = false): array
    {
        // Check if restaurant is ready for scraping
        if (!$restaurant->isReadyForScraping() && !$forceUpdate) {
            return [
                'status' => 'skipped',
                'message' => 'Restaurant is not configured for scraping',
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->name
            ];
        }

        // Check if restaurant is due for scraping based on frequency
        if (!$forceUpdate && $restaurant->last_menu_scrape) {
            $dueForScraping = false;
            
            switch ($restaurant->menu_scrape_frequency) {
                case 'daily':
                    $dueForScraping = $restaurant->last_menu_scrape->diffInHours(now()) >= 24;
                    break;
                case 'weekly':
                    $dueForScraping = $restaurant->last_menu_scrape->diffInDays(now()) >= 7;
                    break;
                case 'monthly':
                    $dueForScraping = $restaurant->last_menu_scrape->diffInDays(now()) >= 30;
                    break;
                default:
                    $dueForScraping = true;
            }
            
            if (!$dueForScraping) {
                return [
                    'status' => 'skipped',
                    'message' => 'Restaurant is not due for scraping yet',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'last_scrape' => $restaurant->last_menu_scrape->toDateTimeString()
                ];
            }
        }

        // Start scraping log
        $scrapingLog = $this->scrapingLogService->startScraping($restaurant, 'manual');
        
        try {
            // Check if URL is accessible and respects robots.txt
            if (!$this->webScrapingService->isUrlAccessible($restaurant->menu_url)) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "URL is not accessible: {$restaurant->menu_url}",
                    'url_inaccessible'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'URL is not accessible',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url
                ];
            }
            
            if (!$this->webScrapingService->respectsRobotsTxt($restaurant->menu_url)) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "URL does not respect robots.txt: {$restaurant->menu_url}",
                    'robots_txt_disallowed'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'URL does not respect robots.txt',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url
                ];
            }
            
            // Fetch menu content
            $menuContent = $this->webScrapingService->fetchMenuContent($restaurant->menu_url);
            
            if (!$menuContent) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "Failed to fetch menu content from: {$restaurant->menu_url}",
                    'content_fetch_failed'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'Failed to fetch menu content',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url
                ];
            }
            
            // Parse menu content
            $parsedMenu = $this->menuParsingService->parseMenuContent(
                $menuContent['content'],
                $restaurant->menu_url,
                $menuContent['type']
            );
            
            if (empty($parsedMenu['items'])) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "No menu items found in content from: {$restaurant->menu_url}",
                    'no_items_found'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'No menu items found in content',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url
                ];
            }
            
            // Validate menu structure
            $structureValidation = $this->menuContentValidationService->validateMenuStructure($parsedMenu);
            if (!$structureValidation['is_valid']) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "Invalid menu structure: " . implode(', ', $structureValidation['errors']),
                    'validation_error'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'Invalid menu structure',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url,
                    'validation_errors' => $structureValidation['errors']
                ];
            }
            
            // Validate and sanitize menu items
            $validationResult = $this->menuContentValidationService->validateAndSanitizeMenuItems($parsedMenu['items']);
            
            // Check if we have any valid items after validation
            if (empty($validationResult['items'])) {
                $this->scrapingLogService->failScraping(
                    $scrapingLog, 
                    "All menu items failed validation from: {$restaurant->menu_url}",
                    'validation_error'
                );
                
                return [
                    'status' => 'failed',
                    'message' => 'All menu items failed validation',
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url,
                    'validation_errors' => $validationResult['errors']
                ];
            }
            
            // If some items failed validation but we still have valid items, continue with partial success
            $isPartialSuccess = $validationResult['total_invalid'] > 0;
            
            // Replace the original items with sanitized ones
            $parsedMenu['items'] = $validationResult['items'];
            
            // Store menu data in database
            DB::beginTransaction();
            try {
                $menuData = [
                    'name' => 'Main Menu',
                    'menu_type' => 'scraped',
                    'is_active' => true,
                    'scraped_at' => now(),
                    'source_url' => $restaurant->menu_url
                ];
                
                $result = $this->menuStorageService->processMenuData(
                    $restaurant,
                    $menuData,
                    $parsedMenu['items']
                );
                
                // Update restaurant's last scrape timestamp
                $restaurant->last_menu_scrape = now();
                $restaurant->save();
                
                DB::commit();
                
                // Check if this was a partial success (some items failed validation)
                if (isset($isPartialSuccess) && $isPartialSuccess) {
                    // Log partial success
                    $this->scrapingLogService->partialScraping(
                        $scrapingLog,
                        count($parsedMenu['items']) + $validationResult['total_invalid'],
                        $result['items_created'],
                        $result['items_updated'],
                        "Some menu items failed validation and were skipped"
                    );
                    
                    return [
                        'status' => 'partial',
                        'message' => 'Menu scraped with some validation issues',
                        'restaurant_id' => $restaurant->id,
                        'restaurant_name' => $restaurant->name,
                        'url' => $restaurant->menu_url,
                        'items_found' => count($parsedMenu['items']) + $validationResult['total_invalid'],
                        'items_valid' => count($parsedMenu['items']),
                        'items_invalid' => $validationResult['total_invalid'],
                        'items_created' => $result['items_created'],
                        'items_updated' => $result['items_updated'],
                        'menu_id' => $result['menu']->id,
                        'validation_errors' => $validationResult['errors']
                    ];
                } else {
                    // Complete scraping log for full success
                    $this->scrapingLogService->completeScraping(
                        $scrapingLog,
                        count($parsedMenu['items']),
                        $result['items_created'],
                        $result['items_updated']
                    );
                    
                    return [
                        'status' => 'success',
                        'message' => 'Menu scraped successfully',
                        'restaurant_id' => $restaurant->id,
                        'restaurant_name' => $restaurant->name,
                        'url' => $restaurant->menu_url,
                        'items_found' => count($parsedMenu['items']),
                        'items_created' => $result['items_created'],
                        'items_updated' => $result['items_updated'],
                        'menu_id' => $result['menu']->id
                    ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                
                // Attempt to recover from database errors
                Log::error('Database error during menu storage', [
                    'restaurant_id' => $restaurant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Try to store at least the scraping log
                try {
                    $this->scrapingLogService->failScraping(
                        $scrapingLog,
                        "Database error during storage: " . $e->getMessage(),
                        'storage_error'
                    );
                } catch (Exception $logException) {
                    Log::critical('Failed to log scraping failure', [
                        'original_error' => $e->getMessage(),
                        'log_error' => $logException->getMessage()
                    ]);
                }
                
                return [
                    'status' => 'failed',
                    'message' => 'Database error during menu storage: ' . $e->getMessage(),
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'url' => $restaurant->menu_url,
                    'error_type' => 'storage_error'
                ];
            }
        } catch (Exception $e) {
            // Log the error and mark scraping as failed
            Log::error('Menu scraping failed', [
                'restaurant_id' => $restaurant->id,
                'url' => $restaurant->menu_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->scrapingLogService->failScraping(
                $scrapingLog, 
                "Error during scraping: {$e->getMessage()}",
                'exception'
            );
            
            return [
                'status' => 'failed',
                'message' => 'Error during scraping: ' . $e->getMessage(),
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->name,
                'url' => $restaurant->menu_url
            ];
        }
    }

    /**
     * Scrape menu data for multiple restaurants.
     *
     * @param array $restaurantIds
     * @param bool $forceUpdate Force update even if not due for scraping
     * @return array Results for each restaurant
     */
    public function scrapeMultipleRestaurants(array $restaurantIds, bool $forceUpdate = false): array
    {
        $results = [];
        $restaurants = Restaurant::whereIn('id', $restaurantIds)->get();
        
        foreach ($restaurants as $restaurant) {
            // Apply rate limiting between requests
            if (!empty($results)) {
                sleep($this->rateLimitDelay);
            }
            
            $results[$restaurant->id] = $this->scrapeRestaurant($restaurant, $forceUpdate);
        }
        
        return $results;
    }

    /**
     * Get scraping history for a restaurant.
     *
     * @param Restaurant $restaurant
     * @param int $limit
     * @return Collection
     */
    public function getScrapingHistory(Restaurant $restaurant, int $limit = 10): Collection
    {
        return $this->scrapingLogService->getScrapingHistory($restaurant, $limit);
    }

    /**
     * Get restaurants that need scraping based on their frequency settings.
     *
     * @param int $limit Maximum number of restaurants to return
     * @return Collection
     */
    public function getRestaurantsNeedingScraping(int $limit = 10): Collection
    {
        return Restaurant::scrapingEnabled()
            ->needsScraping()
            ->limit($limit)
            ->get();
    }

    /**
     * Get scraping status summary.
     *
     * @param int $days Number of days to include in the summary
     * @return array
     */
    public function getScrapingStatusSummary(int $days = 7): array
    {
        $statistics = $this->scrapingLogService->getRecentStatistics($days);
        $errorStats = $this->scrapingLogService->getErrorStatistics($days);
        $performanceMetrics = $this->scrapingLogService->getPerformanceMetrics($days);
        
        return [
            'period_days' => $days,
            'total_scrapes' => $statistics['total_scrapes'] ?? 0,
            'successful_scrapes' => $statistics['successful_scrapes'] ?? 0,
            'failed_scrapes' => $statistics['failed_scrapes'] ?? 0,
            'partial_scrapes' => $statistics['partial_scrapes'] ?? 0,
            'success_rate' => $statistics['success_rate'] ?? 0,
            'total_items_found' => $statistics['total_items_found'] ?? 0,
            'total_items_created' => $statistics['total_items_created'] ?? 0,
            'total_items_updated' => $statistics['total_items_updated'] ?? 0,
            'error_categories' => $errorStats,
            'performance' => $performanceMetrics,
        ];
    }
}