<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\ScrapingLog;
use Illuminate\Support\Collection;

interface ScrapingLogServiceInterface
{
    /**
     * Start a new scraping log entry.
     */
    public function startScraping(Restaurant $restaurant, string $scrapingType): ScrapingLog;

    /**
     * Complete a scraping log with success status.
     */
    public function completeScraping(
        ScrapingLog $log,
        int $itemsFound = 0,
        int $itemsCreated = 0,
        int $itemsUpdated = 0
    ): ScrapingLog;

    /**
     * Mark a scraping log as failed.
     */
    public function failScraping(ScrapingLog $log, string $errorMessage, string $errorCategory = null): ScrapingLog;

    /**
     * Mark a scraping log as partially successful.
     */
    public function partialScraping(
        ScrapingLog $log,
        int $itemsFound = 0,
        int $itemsCreated = 0,
        int $itemsUpdated = 0,
        string $warningMessage = null
    ): ScrapingLog;

    /**
     * Get scraping history for a restaurant.
     */
    public function getScrapingHistory(Restaurant $restaurant, int $limit = 50): Collection;

    /**
     * Get recent scraping statistics.
     */
    public function getRecentStatistics(int $days = 7): array;

    /**
     * Get error statistics by category.
     */
    public function getErrorStatistics(int $days = 30): array;

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(int $days = 7): array;

    /**
     * Get failed scraping logs that need attention.
     */
    public function getFailedScrapings(int $days = 7): Collection;

    /**
     * Clean up old scraping logs.
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int;
}