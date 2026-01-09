<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/restaurant', [RestaurantController::class, 'index']);
Route::get('/restaurant/{restaurant}', [RestaurantController::class, 'show']);

Route::get('/health-check', function () {
    return response('OK', 200);
});

// Special route for Playwright testing - only enabled in local/testing environments
if (app()->environment(['local', 'testing'])) {
    Route::get('/testing/login-as-admin', function () {
        // Create admin user if it doesn't exist
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => true
            ]
        );
        
        // Login as admin
        auth()->login($admin);
        
        // Redirect to dashboard
        return redirect()->route('admin.dashboard');
    })->name('testing.login-as-admin');
}
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
        Route::post('/admin/add-restaurants', [App\Http\Controllers\AdminController::class, 'addRestaurantsFromLocation'])->name('admin.add-restaurants');
        
        // Menu Scraping Routes
        Route::get('/admin/menu-scraping', [App\Http\Controllers\MenuScrapingController::class, 'dashboard'])->name('admin.menu-scraping.dashboard');
        Route::post('/admin/menu-scraping/restaurant/{restaurant}', [App\Http\Controllers\MenuScrapingController::class, 'scrapeRestaurant'])->name('admin.menu-scraping.scrape-restaurant');
        Route::post('/admin/menu-scraping/multiple', [App\Http\Controllers\MenuScrapingController::class, 'scrapeMultipleRestaurants'])->name('admin.menu-scraping.scrape-multiple');
        Route::get('/admin/menu-scraping/history/{restaurant}', [App\Http\Controllers\MenuScrapingController::class, 'scrapingHistory'])->name('admin.menu-scraping.history');
        Route::get('/admin/menu-scraping/edit/{restaurant}', [App\Http\Controllers\MenuScrapingController::class, 'editMenuUrl'])->name('admin.menu-scraping.edit-menu-url');
        Route::put('/admin/menu-scraping/update/{restaurant}', [App\Http\Controllers\MenuScrapingController::class, 'updateMenuUrl'])->name('admin.menu-scraping.update-menu-url');
    });
});
