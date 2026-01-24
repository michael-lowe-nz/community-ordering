<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\OrderController;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/restaurant', [RestaurantController::class, 'index']);
Route::get('/restaurant/{restaurant}', [RestaurantController::class, 'show']);
Route::post('/restaurant/{restaurant}/orders', [OrderController::class, 'store'])
    ->name('restaurants.orders.store');
Route::get('restaurant/{restaurant}/orders/create', [App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
Route::get('restaurant/{restaurant}/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('restaurants.orders.show');
Route::post('restaurant/{restaurant}/orders/{order}', [App\Http\Controllers\OrderController::class, 'addToOrder'])->name('restaurants.orders.addToOrder');

Route::get('/health-check', function () {
    return response('OK', 200);
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return view('dashboard');
    })->name('dashboard');
    
    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    });
});
