<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'cuisine_type',
        'price_range',
        'opening_hours',
        'description',
        'website',
        'google_place_id',
        'menu_url',
        'menu_scraping_enabled',
        'last_menu_scrape',
        'menu_scrape_frequency',
        'scraping_notes',
    ];

    protected $casts = [
        'menu_scraping_enabled' => 'boolean',
        'last_menu_scrape' => 'datetime',
    ];

    /**
     * Get the menus for this restaurant.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Get the active menus for this restaurant.
     */
    public function activeMenus(): HasMany
    {
        return $this->hasMany(Menu::class)->where('is_active', true);
    }

    /**
     * Get the scraping logs for this restaurant.
     */
    public function scrapingLogs(): HasMany
    {
        return $this->hasMany(ScrapingLog::class);
    }

    /**
     * Get the most recent scraping log.
     */
    public function latestScrapingLog()
    {
        return $this->hasOne(ScrapingLog::class)->latestOfMany('scraped_at');
    }

    /**
     * Scope to get restaurants with scraping enabled.
     */
    public function scopeScrapingEnabled($query)
    {
        return $query->where('menu_scraping_enabled', true);
    }

    /**
     * Scope to get restaurants that need scraping based on frequency.
     */
    public function scopeNeedsScraping($query)
    {
        return $query->where('menu_scraping_enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_menu_scrape')
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('menu_scrape_frequency', 'daily')
                               ->where('last_menu_scrape', '<', now()->subDay());
                  })
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('menu_scrape_frequency', 'weekly')
                               ->where('last_menu_scrape', '<', now()->subWeek());
                  })
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('menu_scrape_frequency', 'monthly')
                               ->where('last_menu_scrape', '<', now()->subMonth());
                  });
            });
    }

    /**
     * Get validation rules for restaurant model.
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'cuisine_type' => 'nullable|string|max:100',
            'price_range' => 'nullable|string|max:50',
            'opening_hours' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'google_place_id' => 'nullable|string|max:255',
            'menu_url' => 'nullable|url|max:255',
            'menu_scraping_enabled' => 'boolean',
            'last_menu_scrape' => 'nullable|date',
            'menu_scrape_frequency' => 'nullable|in:daily,weekly,monthly',
            'scraping_notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Check if restaurant has a valid menu URL for scraping.
     */
    public function hasValidMenuUrl(): bool
    {
        return !empty($this->menu_url) && filter_var($this->menu_url, FILTER_VALIDATE_URL);
    }

    /**
     * Check if restaurant is ready for scraping.
     */
    public function isReadyForScraping(): bool
    {
        return $this->menu_scraping_enabled && $this->hasValidMenuUrl();
    }
}