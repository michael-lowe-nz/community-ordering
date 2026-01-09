<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController
{
    public function index()
    {
        $restaurants = Restaurant::orderBy('name', 'asc')->get();
        return view('restaurants.index', compact('restaurants'));
    }

    public function show(Restaurant $restaurant)
    {
        // Get active menus with their menu items
        $activeMenus = $restaurant->activeMenus()
            ->with(['menuItems' => function($query) {
                $query->where('is_available', true)
                      ->orderBy('section')
                      ->orderBy('order_index');
            }])
            ->get();
            
        // Group menu items by section for each menu
        $menuSections = [];
        foreach ($activeMenus as $menu) {
            $sections = $menu->menuItems->groupBy('section');
            $menuSections[$menu->id] = $sections;
        }
        
        return view('restaurants.show', compact('restaurant', 'activeMenus', 'menuSections'));
    }
}
