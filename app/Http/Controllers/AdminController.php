<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRestaurants = Restaurant::count();
        return view('admin.dashboard', compact('totalRestaurants'));
    }
}