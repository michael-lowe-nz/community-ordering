<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'content',
        'participant_count',
        'notes',
        'ordered_at',
        'verdict',
        'attendees',
        'attendee_notes',
    ];

    protected $casts = [
        'participant_count' => 'integer',
        'ordered_at' => 'datetime',
        'attendees' => 'array',
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
        return $this->hasMany(OrderItem::class)->orderBy('position')->orderBy('id');
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
     * Metres of pizza in the order: the summed quantity of every line whose
     * dish is sold by the metre.
     */
    public function getPizzaMetresAttribute(): float
    {
        return (float) $this->items
            ->filter(fn (OrderItem $item): bool => $item->menuItem?->unit === MenuItem::UNIT_METRES)
            ->sum('quantity');
    }

    /**
     * Metres of pizza without trailing zeros (2, 2.5, 1.75), or null when the
     * order has none.
     */
    public function getDisplayPizzaMetresAttribute(): ?string
    {
        $metres = $this->pizza_metres;

        return $metres > 0 ? rtrim(rtrim(number_format($metres, 2, '.', ''), '0'), '.') : null;
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

        return $now->translatedFormat('l').' '.$period;
    }

    /**
     * Get the order title, falling back to the day and time of day it was ordered
     * (e.g. "Tuesday night").
     */
    public function getTitleAttribute(): string
    {
        $content = trim((string) ($this->content ?? ''));

        return $content !== '' ? $content : self::defaultTitle($this->ordered_at ?? $this->created_at);
    }

    /**
     * The order as plain text, for pasting into a message or reading out when
     * phoning it through.
     */
    public function toPlainText(): string
    {
        $heading = $this->title.' · '.$this->restaurant->name;
        if ($this->participant_count > 0) {
            $heading .= ' · '.$this->participant_count.' '.Str::plural('person', $this->participant_count);
        }

        $lines = $this->items->map(function (OrderItem $item): string {
            $name = $item->variant ? "{$item->name} ({$item->variant})" : $item->name;

            return "{$name} x {$item->display_quantity}";
        });

        $notes = trim((string) $this->notes);

        return collect([$heading, '', ...$lines, ...($notes !== '' ? ['', $notes] : [])])->implode("\n");
    }
}
