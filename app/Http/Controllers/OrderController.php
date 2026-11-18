<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController
{
    /**
     * Start a new order at a restaurant and open it in the menu builder.
     */
    public function store(Restaurant $restaurant): RedirectResponse
    {
        $order = $restaurant->orders()->create([
            'user_id' => Auth::id(),
            'ordered_at' => now(),
        ]);

        return redirect()->route('orders.build', $order);
    }

    /**
     * Start a new order from a copy of an existing one's dishes and head count.
     */
    public function duplicate(Order $order): RedirectResponse
    {
        $copy = DB::transaction(function () use ($order): Order {
            $copy = $order->restaurant->orders()->create([
                'user_id' => Auth::id(),
                'participant_count' => $order->participant_count,
                'ordered_at' => now(),
            ]);

            $copy->items()->createMany($order->items->map(fn ($item): array => [
                'menu_item_id' => $item->menu_item_id,
                'variant' => $item->variant,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'position' => $item->position,
            ])->all());

            return $copy;
        });

        return redirect()->route('orders.build', $copy);
    }
}
