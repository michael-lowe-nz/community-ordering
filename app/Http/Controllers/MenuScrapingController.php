<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\MenuScrapingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class MenuScrapingController extends Controller
{
    protected MenuScrapingServiceInterface $menuScrapingService;

    /**
     * Constructor
     */
    public function __construct(MenuScrapingServiceInterface $menuScrapingService)
    {
        $this->menuScrapingService = $menuScrapingService;
        $this->middleware('admin');
    }

    /**
     * Display the menu scraping dashboard.
     */
    public function dashboard(Request $request)
    {
        // Check if we should show all restaurants
        $showAll = $request->has('show_all') && $request->show_all === 'true';
        
        // Get restaurants based on filter
        if ($showAll) {
            $restaurants = Restaurant::all();
        } else {
            $restaurants = Restaurant::scrapingEnabled()->get();
        }
        
        // Get restaurants that need scraping
        $restaurantsNeedingScraping = $this->menuScrapingService->getRestaurantsNeedingScraping(10);
        
        // Get scraping status summary
        $scrapingSummary = $this->menuScrapingService->getScrapingStatusSummary(7);
        
        return view('admin.menu-scraping.dashboard', compact(
            'restaurants',
            'restaurantsNeedingScraping',
            'scrapingSummary',
            'showAll'
        ));
    }

    /**
     * Scrape a single restaurant's menu with enhanced error handling.
     */
    public function scrapeRestaurant(Request $request, Restaurant $restaurant)
    {
        $forceUpdate = $request->has('force') && $request->force === 'true';
        
        $result = $this->menuScrapingService->scrapeRestaurant($restaurant, $forceUpdate);
        
        switch ($result['status']) {
            case 'success':
                return redirect()->route('admin.menu-scraping.history', $restaurant->id)
                    ->with('success', 'Menu scraped successfully. Found ' . ($result['items_found'] ?? 0) . ' items.');
                
            case 'partial':
                $message = 'Menu scraped with some issues. Found ' . ($result['items_found'] ?? 0) . 
                           ' items, but ' . ($result['items_invalid'] ?? 0) . ' items were invalid.';
                
                return redirect()->route('admin.menu-scraping.history', $restaurant->id)
                    ->with('warning', $message)
                    ->with('validation_errors', $result['validation_errors'] ?? []);
                
            case 'skipped':
                return redirect()->route('admin.menu-scraping.history', $restaurant->id)
                    ->with('info', 'Menu scraping skipped: ' . ($result['message'] ?? 'Not due for scraping'));
                
            case 'failed':
                $errorType = $result['error_type'] ?? 'unknown';
                $errorMessage = $result['message'] ?? 'Unknown error';
                
                // Log additional details for debugging
                Log::error('Menu scraping failed in controller', [
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'error_type' => $errorType,
                    'error_message' => $errorMessage
                ]);
                
                return redirect()->route('admin.menu-scraping.history', $restaurant->id)
                    ->with('error', 'Menu scraping failed: ' . $errorMessage)
                    ->with('error_type', $errorType)
                    ->with('validation_errors', $result['validation_errors'] ?? []);
                
            default:
                return redirect()->route('admin.menu-scraping.history', $restaurant->id)
                    ->with('error', 'Menu scraping failed with unknown status: ' . ($result['status'] ?? 'unknown'));
        }
    }

    /**
     * Scrape multiple restaurants' menus with enhanced error handling.
     */
    public function scrapeMultipleRestaurants(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_ids' => 'required|array',
            'restaurant_ids.*' => 'exists:restaurants,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.menu-scraping.dashboard')
                ->withErrors($validator)
                ->withInput();
        }

        $forceUpdate = $request->has('force') && $request->force === 'true';
        
        $results = $this->menuScrapingService->scrapeMultipleRestaurants($request->restaurant_ids, $forceUpdate);
        
        // Track different status counts
        $successCount = 0;
        $partialCount = 0;
        $failCount = 0;
        $skipCount = 0;
        
        // Track error types for better reporting
        $errorTypes = [];
        $validationErrors = [];
        
        foreach ($results as $result) {
            switch ($result['status']) {
                case 'success':
                    $successCount++;
                    break;
                case 'partial':
                    $partialCount++;
                    if (!empty($result['validation_errors'])) {
                        $validationErrors[] = [
                            'restaurant_id' => $result['restaurant_id'],
                            'restaurant_name' => $result['restaurant_name'],
                            'errors' => $result['validation_errors']
                        ];
                    }
                    break;
                case 'failed':
                    $failCount++;
                    $errorType = $result['error_type'] ?? 'unknown';
                    if (!isset($errorTypes[$errorType])) {
                        $errorTypes[$errorType] = 0;
                    }
                    $errorTypes[$errorType]++;
                    break;
                case 'skipped':
                    $skipCount++;
                    break;
            }
        }
        
        // Log detailed results for debugging
        Log::info('Multiple restaurant scraping completed', [
            'success_count' => $successCount,
            'partial_count' => $partialCount,
            'fail_count' => $failCount,
            'skip_count' => $skipCount,
            'error_types' => $errorTypes
        ]);
        
        // Build response message
        if ($successCount > 0 || $partialCount > 0) {
            $message = "Scraped {$successCount} restaurants successfully.";
            
            if ($partialCount > 0) {
                $message .= " {$partialCount} restaurants had partial success with validation issues.";
            }
            
            if ($failCount > 0) {
                $message .= " {$failCount} restaurants failed.";
                
                // Add error type details if available
                if (!empty($errorTypes)) {
                    $errorDetails = [];
                    foreach ($errorTypes as $type => $count) {
                        $errorDetails[] = "{$count} {$type} errors";
                    }
                    $message .= " (" . implode(', ', $errorDetails) . ")";
                }
            }
            
            if ($skipCount > 0) {
                $message .= " {$skipCount} restaurants skipped.";
            }
            
            $flashType = ($failCount > 0 || $partialCount > 0) ? 'warning' : 'success';
            
            return redirect()->route('admin.menu-scraping.dashboard')
                ->with($flashType, $message)
                ->with('validation_errors', $validationErrors)
                ->with('error_types', $errorTypes);
        } else {
            $message = "No restaurants were scraped successfully.";
            
            if ($failCount > 0) {
                $message .= " {$failCount} failed";
                
                // Add error type details if available
                if (!empty($errorTypes)) {
                    $errorDetails = [];
                    foreach ($errorTypes as $type => $count) {
                        $errorDetails[] = "{$count} {$type} errors";
                    }
                    $message .= " (" . implode(', ', $errorDetails) . ")";
                }
                
                $message .= ".";
            }
            
            if ($skipCount > 0) {
                $message .= " {$skipCount} skipped.";
            }
            
            return redirect()->route('admin.menu-scraping.dashboard')
                ->with('error', $message)
                ->with('error_types', $errorTypes);
        }
    }

    /**
     * Display scraping history for a restaurant.
     */
    public function scrapingHistory(Restaurant $restaurant)
    {
        $history = $this->menuScrapingService->getScrapingHistory($restaurant);
        
        return view('admin.menu-scraping.history', compact('restaurant', 'history'));
    }

    /**
     * Show the form for configuring a restaurant's menu URL.
     */
    public function editMenuUrl(Restaurant $restaurant)
    {
        return view('admin.menu-scraping.edit-menu-url', compact('restaurant'));
    }

    /**
     * Update a restaurant's menu URL and scraping configuration.
     */
    public function updateMenuUrl(Request $request, Restaurant $restaurant)
    {
        $validator = Validator::make($request->all(), [
            'menu_url' => 'nullable|url|max:255',
            'menu_scrape_frequency' => 'nullable|in:daily,weekly,monthly',
            'scraping_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.menu-scraping.edit-menu-url', $restaurant->id)
                ->withErrors($validator)
                ->withInput();
        }

        $restaurant->update([
            'menu_url' => $request->menu_url,
            'menu_scraping_enabled' => $request->has('menu_scraping_enabled'),
            'menu_scrape_frequency' => $request->menu_scrape_frequency,
            'scraping_notes' => $request->scraping_notes,
        ]);

        return redirect()->route('admin.menu-scraping.dashboard')
            ->with('success', "Menu URL and scraping configuration updated for {$restaurant->name}");
    }
}