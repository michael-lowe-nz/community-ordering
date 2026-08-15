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
