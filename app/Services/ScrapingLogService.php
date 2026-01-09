<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScrapingLogService implements ScrapingLogServiceInterface
{
    /**
     * Error categories for better tracking and analysis.
     */
    const ERROR_CATEGORIES = [
        'network' => 'Network/Connection Error',
        'parsing' => 'Content Parsing Error',
        'validation' => 'Data Validation Error',
        'storage' => 'Database Storage Error',
        'access' => 'Access Denied/Authentication Error',
        'format' => 'Unsupported Format Error',
        'timeout' => 'Request Timeout Error',
        'rate_limit' => 'Rate Limiting Error',
        'unknown' => 'Unknown Error'
    ];

    /**
     * Start a new scraping log entry.
     */
    public function startScraping(Restaurant $restaurant, string $scrapingType): ScrapingLog
    {
        $log = ScrapingLog::create([
            'restaurant_id' => $restaurant->id,
            'scraping_type' => $scrapingType,
            'status' => 'partial', // Using 'partial' as an initial status since the schema doesn't support 'in_progress'
            'scraped_at' => now(),
        ]);

        Log::info('Started scraping for restaurant', [
            'restaurant_id' => $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'scraping_type' => $scrapingType,
            'log_id' => $log->id,
        ]);

        return $log;
    }

    /**
     * Complete a scraping log with success status.
     */
    public function completeScraping(
        ScrapingLog $log,
        int $itemsFound = 0,
        int $itemsCreated = 0,
        int $itemsUpdated = 0
    ): ScrapingLog {
        $startTime = $log->scraped_at;
        $duration = $startTime ? now()->diffInSeconds($startTime) : null;

        $log->update([
            'status' => 'success',
            'items_found' => $itemsFound,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'duration_seconds' => $duration,
            'error_message' => null,
        ]);

        Log::info('Completed scraping successfully', [
            'restaurant_id' => $log->restaurant_id,
            'restaurant_name' => $log->restaurant->name ?? 'Unknown',
            'scraping_type' => $log->scraping_type,
            'log_id' => $log->id,
            'items_found' => $itemsFound,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'duration_seconds' => $duration,
        ]);

        return $log;
    }

    /**
     * Mark a scraping log as failed.
     */
    public function failScraping(ScrapingLog $log, string $errorMessage, string $errorCategory = null): ScrapingLog
    {
        $startTime = $log->scraped_at;
        $duration = $startTime ? now()->diffInSeconds($startTime) : null;
        
        $categorizedError = $this->categorizeError($errorMessage, $errorCategory);

        $log->update([
            'status' => 'failed',
            'error_message' => $categorizedError,
            'duration_seconds' => $duration,
        ]);

        Log::error('Scraping failed', [
            'restaurant_id' => $log->restaurant_id,
            'restaurant_name' => $log->restaurant->name ?? 'Unknown',
            'scraping_type' => $log->scraping_type,
            'log_id' => $log->id,
            'error_message' => $errorMessage,
            'error_category' => $errorCategory,
            'duration_seconds' => $duration,
        ]);

        return $log;
    }

    /**
     * Mark a scraping log as partially successful.
     */
    public function partialScraping(
        ScrapingLog $log,
        int $itemsFound = 0,
        int $itemsCreated = 0,
        int $itemsUpdated = 0,
        string $warningMessage = null
    ): ScrapingLog {
        $startTime = $log->scraped_at;
        $duration = $startTime ? now()->diffInSeconds($startTime) : null;

        $log->update([
            'status' => 'partial',
            'items_found' => $itemsFound,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'error_message' => $warningMessage,
            'duration_seconds' => $duration,
        ]);

        Log::warning('Scraping completed with warnings', [
            'restaurant_id' => $log->restaurant_id,
            'restaurant_name' => $log->restaurant->name ?? 'Unknown',
            'scraping_type' => $log->scraping_type,
            'log_id' => $log->id,
            'items_found' => $itemsFound,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'warning_message' => $warningMessage,
            'duration_seconds' => $duration,
        ]);

        return $log;
    }

    /**
     * Get scraping history for a restaurant.
     */
    public function getScrapingHistory(Restaurant $restaurant, int $limit = 50): Collection
    {
        return ScrapingLog::where('restaurant_id', $restaurant->id)
            ->orderBy('scraped_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent scraping statistics.
     */
    public function getRecentStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $stats = ScrapingLog::where('scraped_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_scrapes,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_scrapes,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_scrapes,
                SUM(CASE WHEN status = "partial" THEN 1 ELSE 0 END) as partial_scrapes,
                SUM(COALESCE(items_found, 0)) as total_items_found,
                SUM(COALESCE(items_created, 0)) as total_items_created,
                SUM(COALESCE(items_updated, 0)) as total_items_updated,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration_seconds,
                COUNT(DISTINCT restaurant_id) as restaurants_scraped
            ')
            ->first();

        $successRate = $stats->total_scrapes > 0 
            ? round(($stats->successful_scrapes / $stats->total_scrapes) * 100, 2)
            : 0;

        return [
            'period_days' => $days,
            'total_scrapes' => $stats->total_scrapes,
            'successful_scrapes' => $stats->successful_scrapes,
            'failed_scrapes' => $stats->failed_scrapes,
            'partial_scrapes' => $stats->partial_scrapes,
            'success_rate_percent' => $successRate,
            'total_items_found' => $stats->total_items_found,
            'total_items_created' => $stats->total_items_created,
            'total_items_updated' => $stats->total_items_updated,
            'avg_duration_seconds' => round($stats->avg_duration_seconds, 2),
            'restaurants_scraped' => $stats->restaurants_scraped,
        ];
    }

    /**
     * Get error statistics by category.
     */
    public function getErrorStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $errorLogs = ScrapingLog::where('scraped_at', '>=', $startDate)
            ->whereIn('status', ['failed', 'partial'])
            ->whereNotNull('error_message')
            ->pluck('error_message');

        $categorizedErrors = [];
        foreach (self::ERROR_CATEGORIES as $category => $description) {
            $categorizedErrors[$category] = [
                'description' => $description,
                'count' => 0,
                'percentage' => 0,
            ];
        }

        $totalErrors = $errorLogs->count();
        
        foreach ($errorLogs as $errorMessage) {
            $category = $this->extractErrorCategory($errorMessage);
            if (isset($categorizedErrors[$category])) {
                $categorizedErrors[$category]['count']++;
            }
        }

        // Calculate percentages
        if ($totalErrors > 0) {
            foreach ($categorizedErrors as $category => &$data) {
                $data['percentage'] = round(($data['count'] / $totalErrors) * 100, 2);
            }
        }

        return [
            'period_days' => $days,
            'total_errors' => $totalErrors,
            'categories' => $categorizedErrors,
        ];
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $metrics = ScrapingLog::where('scraped_at', '>=', $startDate)
            ->whereNotNull('duration_seconds')
            ->selectRaw('
                MIN(duration_seconds) as min_duration,
                MAX(duration_seconds) as max_duration,
                AVG(duration_seconds) as avg_duration,
                COUNT(*) as total_with_duration
            ')
            ->first();

        // Get daily performance trends
        $dailyMetrics = ScrapingLog::where('scraped_at', '>=', $startDate)
            ->selectRaw('
                DATE(scraped_at) as date,
                COUNT(*) as scrapes_count,
                AVG(COALESCE(duration_seconds, 0)) as avg_duration,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'period_days' => $days,
            'min_duration_seconds' => $metrics->min_duration ?? 0,
            'max_duration_seconds' => $metrics->max_duration ?? 0,
            'avg_duration_seconds' => round($metrics->avg_duration ?? 0, 2),
            'total_with_duration' => $metrics->total_with_duration ?? 0,
            'daily_trends' => $dailyMetrics->toArray(),
        ];
    }

    /**
     * Get failed scraping logs that need attention.
     */
    public function getFailedScrapings(int $days = 7): Collection
    {
        return ScrapingLog::with('restaurant')
            ->where('scraped_at', '>=', now()->subDays($days))
            ->where('status', 'failed')
            ->orderBy('scraped_at', 'desc')
            ->get();
    }

    /**
     * Clean up old scraping logs.
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deletedCount = ScrapingLog::where('scraped_at', '<', $cutoffDate)->delete();

        Log::info('Cleaned up old scraping logs', [
            'days_to_keep' => $daysToKeep,
            'cutoff_date' => $cutoffDate->toDateString(),
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Categorize error message with category prefix.
     */
    private function categorizeError(string $errorMessage, string $errorCategory = null): string
    {
        $category = $errorCategory ?? $this->detectErrorCategory($errorMessage);
        $categoryLabel = self::ERROR_CATEGORIES[$category] ?? self::ERROR_CATEGORIES['unknown'];
        
        return "[{$categoryLabel}] {$errorMessage}";
    }

    /**
     * Extract error category from categorized error message.
     */
    private function extractErrorCategory(string $errorMessage): string
    {
        foreach (self::ERROR_CATEGORIES as $category => $description) {
            if (strpos($errorMessage, "[{$description}]") === 0) {
                return $category;
            }
        }
        return 'unknown';
    }

    /**
     * Detect error category based on error message content.
     */
    private function detectErrorCategory(string $errorMessage): string
    {
        $message = strtolower($errorMessage);

        if (strpos($message, 'connection') !== false || strpos($message, 'network') !== false) {
            return 'network';
        }
        
        if (strpos($message, 'timeout') !== false) {
            return 'timeout';
        }
        
        if (strpos($message, 'rate limit') !== false || strpos($message, '429') !== false) {
            return 'rate_limit';
        }
        
        if (strpos($message, 'access denied') !== false || strpos($message, '403') !== false || strpos($message, '401') !== false) {
            return 'access';
        }
        
        if (strpos($message, 'parse') !== false || strpos($message, 'parsing') !== false) {
            return 'parsing';
        }
        
        if (strpos($message, 'validation') !== false || strpos($message, 'invalid') !== false) {
            return 'validation';
        }
        
        if (strpos($message, 'database') !== false || strpos($message, 'storage') !== false) {
            return 'storage';
        }
        
        if (strpos($message, 'format') !== false || strpos($message, 'unsupported') !== false) {
            return 'format';
        }

        return 'unknown';
    }
}