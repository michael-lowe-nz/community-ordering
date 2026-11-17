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
 * - the dishes on the restaurant's active menus (with tags, variants, units),
 * - dishes people have ordered there before that aren't on a menu yet,
 * - how often each dish has been ordered, which decides chip order.
 */
class MenuBuilderCatalog
{
    public static function for(Restaurant $restaurant, ?Order $excluding = null): array
    {
        $uses = self::orderHistory($restaurant, $excluding);

        $menuItems = MenuItem::query()
            ->whereHas('menu', fn ($q) => $q->where('restaurant_id', $restaurant->id)->where('is_active', true))
            ->where('is_available', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();

        $items = $menuItems->values()->map(function (MenuItem $item, int $i) use ($uses) {
            $unit = $item->unit ? [
                'name' => $item->unit,
                'step' => $item->unit_step ?: 1,
                'start' => $item->unit_start ?: ($item->unit_step ?: 1),
            ] : null;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'tags' => array_values($item->tags ?? []),
                'variants' => $item->variants ? array_values($item->variants) : null,
                'unit' => $unit,
                'uses' => $uses[mb_strtolower($item->name)]['uses'] ?? 0,
                'rank' => $i,
            ];
        });

        $onMenu = $items->mapWithKeys(fn ($item) => [mb_strtolower($item['name']) => true]);

        $fromHistory = collect($uses)
            ->reject(fn ($row, $key) => isset($onMenu[$key]))
            ->values()
            ->map(fn ($row, $i) => [
                'id' => null,
                'name' => $row['name'],
                'tags' => [],
                'variants' => null,
                'unit' => null,
                'uses' => $row['uses'],
                'rank' => $items->count() + $i,
            ]);

        // Most ordered first; menu order breaks ties.
        $all = $items->concat($fromHistory)
            ->sort(fn ($a, $b) => [$b['uses'], $a['rank']] <=> [$a['uses'], $b['rank']])
            ->values()
            ->map(function ($item) {
                unset($item['rank']);

                return $item;
            });

        return [
            'items' => $all->all(),
            'tags' => self::tagOrder($items),
        ];
    }

    /**
     * Number of past orders each dish (by case-insensitive name) appeared in.
     *
     * @return array<string, array{name: string, uses: int}>
     */
    private static function orderHistory(Restaurant $restaurant, ?Order $excluding): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.restaurant_id', $restaurant->id)
            ->when($excluding, fn ($q) => $q->where('orders.id', '!=', $excluding->id))
            ->selectRaw('LOWER(order_items.name) as name_key, MIN(order_items.name) as name, COUNT(DISTINCT order_items.order_id) as uses')
            ->groupByRaw('LOWER(order_items.name)')
            ->orderByDesc('uses')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->name_key => ['name' => $row->name, 'uses' => (int) $row->uses]])
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
