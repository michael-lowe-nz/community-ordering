<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;

class DashboardController
{
    /**
     * Show the form for creating the resource.
     */
    public function create(): never
    {
        abort(404);
    }

    /**
     * Store the newly created resource in storage.
     */
    public function store(Request $request): never
    {
        abort(404);
    }

    /**
     * Display the resource.
     */
    public function show()
    {
        $restaurants = Restaurant::orderBy('name', 'asc')->get();
        return view('dashboard', compact('restaurants'));
    }
}
