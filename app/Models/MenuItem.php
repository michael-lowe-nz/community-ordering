<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'name',
        'description',
        'price',
        'section',
        'order_index',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'order_index' => 'integer',
        'is_available' => 'boolean',
    ];

    /**
     * Get the menu that owns this menu item.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the restaurant through the menu relationship.
     */
    public function restaurant()
    {
        return $this->hasOneThrough(Restaurant::class, Menu::class, 'id', 'id', 'menu_id', 'restaurant_id');
    }

    /**
     * Scope to get only available menu items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get menu items by section.
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Get validation rules for menu item model.
     */
    public static function validationRules(): array
    {
        return [
            'menu_id' => 'required|exists:menus,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'section' => 'nullable|string|max:100',
            'order_index' => 'nullable|integer|min:0',
            'is_available' => 'boolean',
        ];
    }

    /**
     * Check if this menu item is available.
     */
    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    /**
     * Check if this menu item has a price.
     */
    public function hasPrice(): bool
    {
        return !is_null($this->price) && $this->price > 0;
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPrice(): string
    {
        if (!$this->hasPrice()) {
            return 'Price not available';
        }
        
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the section name or default.
     */
    public function getSectionName(): string
    {
        return $this->section ?? 'Other';
    }
}