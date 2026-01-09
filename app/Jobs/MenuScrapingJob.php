<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\MenuScrapingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ScrapingFailureNotification;
use App\Models\User;

class MenuScrapingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The restaurant to scrape.
     *
     * @var \App\Models\Restaurant|null
     */
    protected $restaurant;

    /**
     * The frequency to scrape (daily, weekly, monthly).
     *
     * @var string|null
     */
    protected $frequency;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Restaurant|null $restaurant
     * @param string|null $frequency
     * @return void
     */
    public function __construct(?Restaurant $restaurant = null, ?string $frequency = null)
    {
        $this->restaurant = $restaurant;
        $this->frequency = $frequency;
    }

    /**
     * Execute the job.
     *
     * @param \App\Services\MenuScrapingServiceInterface $menuScrapingService
     * @return void
     */
    public function handle(MenuScrapingServiceInterface $menuScrapingService)
    {
        try {
            if ($this->restaurant) {
                // Scrape a specific restaurant
                Log::info('Starting scheduled scraping job for restaurant', [
                    'restaurant_id' => $this->restaurant->id,
                    'restaurant_name' => $this->restaurant->name,
                ]);

                $result = $menuScrapingService->scrapeRestaurant($this->restaurant);
                
                $this->processScrapingResult($result);
            } else {
                // Scrape restaurants based on frequency
                Log::info('Starting scheduled scraping job for frequency', [
                    'frequency' => $this->frequency ?? 'all',
                ]);

                $restaurants = $this->getRestaurantsForScraping();
                
                if ($restaurants->isEmpty()) {
                    Log::info('No restaurants found for scheduled scraping', [
                        'frequency' => $this->frequency ?? 'all',
                    ]);
                    return;
                }

                $results = $menuScrapingService->scrapeMultipleRestaurants($restaurants->pluck('id')->toArray());
                
                foreach ($results as $restaurantId => $result) {
                    $this->processScrapingResult($result);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in scheduled menu scraping job', [
                'restaurant_id' => $this->restaurant->id ?? 'multiple',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->notifyAdminsOfFailure($e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Get restaurants that need scraping based on frequency.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRestaurantsForScraping()
    {
        $query = Restaurant::scrapingEnabled();

        if ($this->frequency) {
            // Filter by specific frequency
            $query->where('menu_scrape_frequency', $this->frequency);
        }

        return $query->needsScraping()->get();
    }

    /**
     * Process the result of a scraping operation.
     *
     * @param array $result
     * @return void
     */
    protected function processScrapingResult(array $result)
    {
        $restaurantId = $result['restaurant_id'] ?? null;
        
        if ($result['status'] === 'failed') {
            Log::warning('Scheduled scraping failed for restaurant', [
                'restaurant_id' => $restaurantId,
                'restaurant_name' => $result['restaurant_name'] ?? 'Unknown',
                'message' => $result['message'] ?? 'Unknown error',
            ]);

            $this->notifyAdminsOfFailure(
                $result['message'] ?? 'Unknown error',
                Restaurant::find($restaurantId)
            );
        } elseif ($result['status'] === 'success') {
            Log::info('Scheduled scraping completed successfully for restaurant', [
                'restaurant_id' => $restaurantId,
                'restaurant_name' => $result['restaurant_name'] ?? 'Unknown',
                'items_found' => $result['items_found'] ?? 0,
                'items_created' => $result['items_created'] ?? 0,
                'items_updated' => $result['items_updated'] ?? 0,
            ]);
        }
    }

    /**
     * Notify administrators of scraping failures.
     *
     * @param string $errorMessage
     * @param \App\Models\Restaurant|null $restaurant
     * @return void
     */
    protected function notifyAdminsOfFailure(string $errorMessage, ?Restaurant $restaurant = null)
    {
        // Get all admin users
        $admins = User::where('is_admin', true)->get();
        
        if ($admins->isEmpty()) {
            Log::warning('No admin users found to notify about scraping failure');
            return;
        }

        Notification::send($admins, new ScrapingFailureNotification(
            $errorMessage,
            $restaurant,
            $this->frequency
        ));
    }
}