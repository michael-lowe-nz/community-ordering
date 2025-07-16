<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapingLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'restaurant_id',
        'scraping_type',
        'status',
        'items_found',
        'items_created',
        'items_updated',
        'error_message',
        'scraped_at',
        'duration_seconds',
    ];

    protected $casts = [
        'scraped_at' => 'datetime',
        'items_found' => 'integer',
        'items_created' => 'integer',
        'items_updated' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the restaurant that this log belongs to.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Scope to get successful scraping logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get failed scraping logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get recent scraping logs.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('scraped_at', '>=', now()->subDays($days));
    }

    /**
     * Get validation rules for scraping log model.
     */
    public static function validationRules(): array
    {
        return [
            'restaurant_id' => 'required|exists:restaurants,id',
            'scraping_type' => 'required|in:menu,full',
            'status' => 'required|in:success,failed,partial',
            'items_found' => 'nullable|integer|min:0',
            'items_created' => 'nullable|integer|min:0',
            'items_updated' => 'nullable|integer|min:0',
            'error_message' => 'nullable|string|max:1000',
            'scraped_at' => 'required|date',
            'duration_seconds' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Check if the scraping was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the scraping failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the total items processed.
     */
    public function getTotalItemsProcessed(): int
    {
        return ($this->items_created ?? 0) + ($this->items_updated ?? 0);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration_seconds) {
            return 'Unknown';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . ' seconds';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        return $minutes . 'm ' . $seconds . 's';
    }
}