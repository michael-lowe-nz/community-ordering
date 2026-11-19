<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuBuilderController;
use App\Http\Controllers\OrderController;
use App\Livewire\OrderShow;
use App\Livewire\RestaurantView;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/restaurants/{restaurant}', RestaurantView::class)->name('restaurants.view');
Route::get('/orders/{order}', OrderShow::class)->name('orders.show');

// Creating and editing orders needs an account; reading them doesn't.
Route::middleware('auth')->group(function () {
    Route::post('/restaurants/{restaurant}/orders', [OrderController::class, 'store'])->name('restaurants.orders.store');
    Route::post('/orders/{order}/duplicate', [OrderController::class, 'duplicate'])->name('orders.duplicate');

    // Links guests follow to log in; once logged in they land back where they were heading.
    Route::get('/restaurants/{restaurant}/start', [OrderController::class, 'start'])->name('restaurants.start');
    Route::get('/orders/{order}/reuse', [OrderController::class, 'reuse'])->name('orders.reuse');

    // Only the person who started an order can change it.
    Route::middleware('can:update,order')->group(function () {
        // Menu builder: build what the group is ordering from a restaurant's menu.
        Route::get('/orders/{order}/build', [MenuBuilderController::class, 'show'])->name('orders.build');
        Route::put('/orders/{order}/build', [MenuBuilderController::class, 'sync'])->name('orders.build.sync');
        Route::delete('/orders/{order}', [OrderController::class, 'discard'])->name('orders.discard');
    });
});

Route::get('/health-check', function () {
    return response('OK', 200);
});
