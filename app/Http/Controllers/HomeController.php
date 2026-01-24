<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class HomeController
{
    public function index()
    {
        $restaurants = Restaurant::withCount('orders')
            // ->having('orders_count', '>', 0)
            ->orderBy('orders_count', 'desc')
            ->get();
        return view('home')->with('restaurants', $restaurants);
    }
}
