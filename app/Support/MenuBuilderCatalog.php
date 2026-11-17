<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Support\Collection;

/**
 * Everything the menu builder needs to know about a restaurant up front, so
 * the page can boot with no extra requests:
 *
 * - the dishes on the restaurant's menus (with tags, variants, units),
 *   including ones added by typing them into an order,
 * - how often each dish has been ordered, which decides chip order.
 */
class MenuBuilderCatalog
{
    public static function for(Restaurant $restaurant, ?Order $excluding = null): array
    {
        $uses = self::orderHistory($restaurant, $excluding);

        $items = MenuItem::query()
            ->whereIn('menu_id', $restaurant->menus()->select('id'))
            ->where('is_available', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get()
            ->unique(fn (MenuItem $item) => mb_strtolower($item->name))
            ->values()
            ->map(fn (MenuItem $item, int $i) => [
                'id' => $item->id,
                'name' => $item->name,
                'tags' => array_values($item->tags ?? []),
                'variants' => $item->variants ? array_values($item->variants) : null,
                'unit' => $item->unit ? [
                    'name' => $item->unit,
                    'step' => $item->unit_step ?: 1,
                    'start' => $item->unit_start ?: ($item->unit_step ?: 1),
                ] : null,
                'uses' => $uses[$item->id] ?? 0,
                'rank' => $i,
            ]);

        // Most ordered first; menu order breaks ties.
        $sorted = $items
            ->sort(fn ($a, $b) => [$b['uses'], $a['rank']] <=> [$a['uses'], $b['rank']])
            ->values()
            ->map(function ($item) {
                unset($item['rank']);

                return $item;
            });

        return [
            'items' => $sorted->all(),
            'tags' => self::tagOrder($items),
        ];
    }

    /**
     * Number of past orders each menu item appeared in, keyed by menu item id.
     *
     * @return array<int, int>
     */
    private static function orderHistory(Restaurant $restaurant, ?Order $excluding): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.restaurant_id', $restaurant->id)
            ->whereNotNull('order_items.menu_item_id')
            ->when($excluding, fn ($q) => $q->where('orders.id', '!=', $excluding->id))
            ->selectRaw('order_items.menu_item_id, COUNT(DISTINCT order_items.order_id) as uses')
            ->groupBy('order_items.menu_item_id')
            ->pluck('uses', 'menu_item_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Filter tags, most common first, then in the order they first appear.
     */
    private static function tagOrder(Collection $items): array
    {
        $counts = [];
        foreach ($items as $item) {
            foreach ($item['tags'] as $tag) {
                $counts[$tag] ??= ['n' => 0, 'first' => count($counts)];
                $counts[$tag]['n']++;
            }
        }

        uksort($counts, fn ($a, $b) => [$counts[$b]['n'], $counts[$a]['first']] <=> [$counts[$a]['n'], $counts[$b]['first']]);

        return array_keys($counts);
    }
}
