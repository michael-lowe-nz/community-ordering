<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\MenuBuilderCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The menu builder: a React page for putting together what a group will order
 * from a restaurant. The page boots from the props rendered here and saves the
 * whole menu back to sync() as the user edits it.
 */
class MenuBuilderController
{
    public function show(Order $order): View
    {
        $restaurant = $order->restaurant;
        $order->load('items.menuItem');

        $props = [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'url' => route('restaurants.view', $restaurant),
            ],
            'order' => [
                'id' => $order->id,
                'title' => (string) ($order->content ?? ''),
                'defaultTitle' => Order::defaultTitle($order->ordered_at ?? $order->created_at),
                'people' => $order->participant_count ?: 4,
                'createdAt' => $order->created_at?->toIso8601String(),
                'items' => $order->items->filter(fn (OrderItem $item) => $item->menuItem)->map(fn (OrderItem $item) => [
                    'menuItemId' => $item->menu_item_id,
                    'name' => $item->name,
                    'variant' => $item->variant ?? '',
                    'qty' => (float) $item->quantity,
                ])->values(),
            ],
            'catalog' => MenuBuilderCatalog::for($restaurant, $order),
            'urls' => [
                'sync' => route('orders.build.sync', $order),
                'done' => route('orders.show', ['order' => $order, 'saved' => 1]),
                'discard' => route('orders.discard', $order),
            ],
        ];

        return view('menu-builder', compact('restaurant', 'order', 'props'));
    }

    /**
     * Replace the order's title, lines and head count with what the builder sent.
     * A blank title falls back to the day and time of day it was ordered.
     * Dishes typed in that aren't on the menu yet are added to it.
     */
    public function sync(Request $request, Order $order): JsonResponse
    {
        $restaurant = $order->restaurant;

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'people' => ['required', 'integer', 'min:1', 'max:99'],
            'items' => ['present', 'array', 'max:200'],
            'items.*.menuItemId' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'items.*.tags' => ['nullable', 'array', 'max:20'],
            'items.*.tags.*' => ['string', 'max:50'],
        ]);

        $ids = collect($data['items'])->pluck('menuItemId')->filter()->unique();
        if ($ids->isNotEmpty()) {
            $valid = MenuItem::whereIn('id', $ids)
                ->whereIn('menu_id', $restaurant->menus()->select('id'))
                ->pluck('id');
            if ($valid->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'One or more dishes are not on this restaurant’s menu.']);
            }
        }

        $created = [];

        DB::transaction(function () use ($restaurant, $order, $data, &$created) {
            $lines = collect($data['items'])->values()->map(function ($line, $i) use ($restaurant, &$created) {
                $menuItemId = $line['menuItemId'] ?? null;

                if (! $menuItemId) {
                    $item = $restaurant->findOrCreateMenuItem($line['name'], ['tags' => $line['tags'] ?? []]);
                    $menuItemId = $created[$item->name] = $item->id;
                }

                return [
                    'menu_item_id' => $menuItemId,
                    'variant' => ($line['variant'] ?? '') !== '' ? $line['variant'] : null,
                    'quantity' => round((float) $line['qty'], 2),
                    'position' => $i,
                ];
            });

            $order->participant_count = $data['people'];
            if (array_key_exists('title', $data)) {
                $order->content = trim((string) $data['title']) ?: null;
            }
            $order->save();
            $order->items()->delete();
            $order->items()->createMany($lines->all());
        });

        return response()->json([
            'saved' => true,
            'count' => count($data['items']),
            'menuItemIds' => (object) $created,
            'savedAt' => now()->toIso8601String(),
        ]);
    }
}
