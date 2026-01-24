<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class HomeController
{
    public function index()
    {
        $restaurants = Restaurant::orderBy('name', 'asc')->get();
        return view('home')->with('restaurants', $restaurants);
    }
}
