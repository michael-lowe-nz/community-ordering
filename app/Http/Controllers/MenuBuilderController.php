<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Support\MenuBuilderCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The menu builder: a React page for putting together what a group will order
 * from a restaurant. The page boots from the props rendered here and saves the
 * whole menu back to sync() as the user edits it.
 */
class MenuBuilderController
{
    public function show(Restaurant $restaurant, Order $order)
    {
        $order->load('items');

        $props = [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'url' => url("/restaurant/{$restaurant->id}"),
            ],
            'order' => [
                'id' => $order->id,
                'people' => $order->participant_count ?: 4,
                'createdAt' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'menuItemId' => $item->menu_item_id,
                    'name' => $item->name,
                    'variant' => $item->variant ?? '',
                    'qty' => (float) $item->quantity,
                ])->values(),
            ],
            'catalog' => MenuBuilderCatalog::for($restaurant, $order),
            'urls' => [
                'sync' => route('restaurants.orders.build.sync', [$restaurant, $order]),
                'done' => route('restaurants.orders.show', [$restaurant, $order]),
            ],
        ];

        return view('menu-builder', compact('restaurant', 'order', 'props'));
    }

    /**
     * Replace the order's lines and head count with what the builder sent.
     */
    public function sync(Request $request, Restaurant $restaurant, Order $order): JsonResponse
    {
        $data = $request->validate([
            'people' => ['required', 'integer', 'min:1', 'max:99'],
            'items' => ['present', 'array', 'max:200'],
            'items.*.menuItemId' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'max:9999'],
        ]);

        $ids = collect($data['items'])->pluck('menuItemId')->filter()->unique();
        if ($ids->isNotEmpty()) {
            $valid = MenuItem::whereIn('id', $ids)
                ->whereHas('menu', fn ($q) => $q->where('restaurant_id', $restaurant->id))
                ->pluck('id');
            if ($valid->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'One or more dishes are not on this restaurant’s menu.']);
            }
        }

        DB::transaction(function () use ($order, $data) {
            $order->update(['participant_count' => $data['people']]);
            $order->items()->delete();
            $order->items()->createMany(collect($data['items'])->values()->map(fn ($line, $i) => [
                'menu_item_id' => $line['menuItemId'] ?? null,
                'name' => trim($line['name']),
                'variant' => ($line['variant'] ?? '') !== '' ? $line['variant'] : null,
                'quantity' => round((float) $line['qty'], 2),
                'position' => $i,
            ])->all());
        });

        return response()->json([
            'saved' => true,
            'count' => count($data['items']),
            'savedAt' => now()->toIso8601String(),
        ]);
    }
}
