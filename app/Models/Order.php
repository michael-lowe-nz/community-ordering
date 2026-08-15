<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'content',
        'participant_count',
    ];

    protected $casts = [
        'participant_count' => 'integer',
    ];

    /**
     * Get the restaurant that owns this order.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the user who created this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the menu items in this order through many-to-many relationship.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_order')
            ->withPivot('quantity', 'price', 'special_requests')
            ->withTimestamps();
    }

    /**
     * Get the formatted creation time.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /** Get the short formatted date of creation time. */
    public function getShortFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M j, Y');
    }

    public static function defaultTitle(?\DateTimeInterface $date = null): string
    {
        $now = $date ? Carbon::instance($date) : now();

        $period = match (true) {
            $now->hour >= 22 || $now->hour < 5 => 'night',
            $now->hour < 12 => 'morning',
            $now->hour < 15 => 'lunch',
            $now->hour < 19 => 'afternoon',
            default => 'night',
        };

        return $now->translatedFormat('l') . ' ' . $period;
    }

    /** Get the order title with a sensible fallback. */
    public function getTitleAttribute(): string
    {
        $content = trim((string) ($this->content ?? ''));

        return $content !== '' ? $content : self::defaultTitle();
    }
}
