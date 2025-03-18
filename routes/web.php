<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/restaurant', [RestaurantController::class, 'index']);
Route::get('/restaurant/{restaurant}', [RestaurantController::class, 'show']);

Route::get('/health-check', function () {
    return response('OK', 200);
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
});
