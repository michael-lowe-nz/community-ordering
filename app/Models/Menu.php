<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'menu_type',
        'is_active',
        'scraped_at',
        'source_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scraped_at' => 'datetime',
    ];

    /**
     * Get the restaurant that owns this menu.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the menu items for this menu.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Get active menu items ordered by section and order_index.
     */
    public function activeMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->where('is_available', true)
            ->orderBy('section')
            ->orderBy('order_index');
    }

    /**
     * Scope to get only active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only scraped menus.
     */
    public function scopeScraped($query)
    {
        return $query->where('menu_type', 'scraped');
    }

    /**
     * Get validation rules for menu model.
     */
    public static function validationRules(): array
    {
        return [
            'restaurant_id' => 'required|exists:restaurants,id',
            'name' => 'required|string|max:255',
            'menu_type' => 'required|in:scraped,manual,seasonal',
            'is_active' => 'boolean',
            'scraped_at' => 'nullable|date',
            'source_url' => 'nullable|url|max:255',
        ];
    }

    /**
     * Check if this menu is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this menu was scraped.
     */
    public function isScraped(): bool
    {
        return $this->menu_type === 'scraped';
    }

    /**
     * Get the count of menu items in this menu.
     */
    public function getItemsCount(): int
    {
        return $this->menuItems()->count();
    }

    /**
     * Get the count of available menu items in this menu.
     */
    public function getAvailableItemsCount(): int
    {
        return $this->menuItems()->where('is_available', true)->count();
    }
}