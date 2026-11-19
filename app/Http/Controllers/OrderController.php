<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController
{
    /**
     * Start a new order at a restaurant and open it in the menu builder.
     */
    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->validate([
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $order = $restaurant->orders()->create([
            'user_id' => Auth::id(),
            'participant_count' => $data['participant_count'] ?? 0,
            'ordered_at' => now(),
        ]);

        return redirect()->route('orders.build', $order);
    }

    /**
     * Start a new order from a copy of an existing one's dishes and head count.
     * A head count sent with the request replaces the copied one.
     */
    public function duplicate(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $copy = DB::transaction(function () use ($order, $data): Order {
            $copy = $order->restaurant->orders()->create([
                'user_id' => Auth::id(),
                'participant_count' => $data['participant_count'] ?? $order->participant_count,
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

    /**
     * Where "Log in to start an order" leads: after logging in, back to the
     * restaurant with the start sheet open.
     */
    public function start(Restaurant $restaurant): RedirectResponse
    {
        return redirect()->route('restaurants.view', ['restaurant' => $restaurant, 'start' => 1]);
    }

    /**
     * Where "Reuse this order" leads for a guest: after logging in, back to the
     * order, which then makes the copy.
     */
    public function reuse(Order $order): RedirectResponse
    {
        return redirect()->route('orders.show', ['order' => $order, 'reuse' => 1]);
    }

    /**
     * Throw away a new order that was left without any dishes.
     * Orders that have dishes are kept.
     */
    public function discard(Order $order): Response
    {
        if ($order->items()->doesntExist()) {
            $order->delete();
        }

        return response()->noContent();
    }
}
