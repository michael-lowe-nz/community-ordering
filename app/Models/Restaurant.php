<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
    ];

    protected $casts = [];

    /**
     * Get the formatted location (suburb, city).
     */
    public function getLocationAttribute(): string
    {
        $parts = array_filter([
            $this->suburb,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the menus for this restaurant.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Get the orders for this restaurant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the order items for this restaurant through orders.
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, Order::class);
    }

    /**
     * Find a dish on any of this restaurant's menus by name (case-insensitive),
     * or add it to the first menu (creating a default menu if there is none).
     */
    public function findOrCreateMenuItem(string $name, array $attributes = []): MenuItem
    {
        $name = trim($name);

        $existing = MenuItem::query()
            ->whereIn('menu_id', $this->menus()->select('id'))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $menu = $this->menus()->orderBy('id')->first() ?? $this->menus()->create([
            'name' => 'Default Menu',
            'menu_type' => 'manual',
            'is_active' => true,
        ]);

        return $menu->menuItems()->create($attributes + [
            'name' => $name,
            'order_index' => ($menu->menuItems()->max('order_index') ?? -1) + 1,
            'is_available' => true,
        ]);
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
        ];
    }
}
