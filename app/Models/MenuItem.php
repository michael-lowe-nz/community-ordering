<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'tags',
        'variants',
        'unit',
        'unit_step',
        'unit_start',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'order_index' => 'integer',
        'is_available' => 'boolean',
        'tags' => 'array',
        'variants' => 'array',
        'unit_step' => 'float',
        'unit_start' => 'float',
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
     * Get the orders that contain this menu item.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'menu_item_order')
            ->withPivot('quantity', 'price', 'special_requests')
            ->withTimestamps();
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
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'variants' => 'nullable|array',
            'variants.*' => 'string|max:100',
            'unit' => 'nullable|in:whole,kg,pcs',
            'unit_step' => 'nullable|numeric|gt:0',
            'unit_start' => 'nullable|numeric|gt:0',
        ];
    }
}