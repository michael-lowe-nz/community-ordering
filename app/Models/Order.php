<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'text',
    ];

    /**
     * Get the formatted creation time.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the restaurant through the menu relationship.
     */
    public function restaurant()
    {
        return $this->hasOneThrough(Restaurant::class, Menu::class, 'id', 'id', 'menu_id', 'restaurant_id');
    }

    /**
     * Get all items for this order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
