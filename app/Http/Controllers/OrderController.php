<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\OrderItem;

class OrderController
{
    public function show(Restaurant $restaurant, Order $order)
    {
        return view('order.show', compact('restaurant', 'order'))
            ->with('orderItems', $order->items()->get());
    }
    /**
     * Store a newly created resource in storage.
    */
    public function store(Restaurant $restaurant)
    {
        $order = $restaurant->orders()->create([
            'user_id' => auth()->id(),
            'content' => null,
        ]);
        return redirect()
            ->route('restaurants.orders.show', [$restaurant, $order])
            ->with('message', 'Order created successfully');
    //
    }

    public function addToOrder(Restaurant $restaurant, Order $order, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        $orderItem = new OrderItem([
            'name' => $request->input('name'),
            'quantity' => $request->input('quantity'),
        ]);

        $order->items()->save($orderItem);
            
        return redirect()
            ->route('restaurants.orders.show', [$restaurant, $order])
            ->with('message', 'Item added to order successfully');
    }

    /**
     * Display the specified resource.
     */
    // public function show(Order $order)
    // {
    //     return view('order.show', compact('order'));
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
