<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'variant',
        'quantity',
        'notes',
        'position',
    ];

    protected $casts = [
        'quantity' => 'float',
        'position' => 'integer',
    ];

    /**
     * Get the order that this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the menu item this order item references.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * The dish name, from the menu item this line references.
     */
    public function getNameAttribute(): string
    {
        return $this->menuItem?->name ?? '';
    }

    /**
     * Quantity without trailing zeros (1, 0.5, 2.5).
     */
    public function getDisplayQuantityAttribute(): string
    {
        return rtrim(rtrim(number_format($this->quantity, 2, '.', ''), '0'), '.');
    }
}
