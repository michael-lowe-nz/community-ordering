<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Restaurant;

class OrderController
{
    public function show(Restaurant $restaurant, Order $order)
    {
        return view('order.show', compact('restaurant', 'order'));
    }
    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $request)
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
