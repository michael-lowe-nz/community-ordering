<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController
{
    public function index(): View
    {
        $restaurants = Restaurant::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->get();

        $yourOrders = Auth::check()
            ? Order::where('user_id', Auth::id())
                ->with('restaurant')
                ->withCount('items')
                ->orderByRaw('coalesce(ordered_at, created_at) desc')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        return view('home', [
            'restaurants' => $restaurants,
            'yourOrders' => $yourOrders,
        ]);
    }
}
