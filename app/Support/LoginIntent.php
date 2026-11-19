<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Describes what a guest was trying to do before they were sent to log in, so
 * the login page can say so. Reads the "intended" URL the auth middleware
 * keeps in the session.
 */
class LoginIntent
{
    public static function describe(?string $intendedUrl): ?string
    {
        if (! $intendedUrl) {
            return null;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($intendedUrl));
        } catch (HttpException) {
            return null;
        }

        return match ($route->getName()) {
            'restaurants.start' => ($restaurant = Restaurant::find($route->parameter('restaurant')))
                ? "Log in to start an order at {$restaurant->name}."
                : null,
            'orders.reuse' => ($order = Order::with('restaurant')->find($route->parameter('order')))
                ? "Log in to reuse “{$order->title}” from {$order->restaurant->name}."
                : null,
            default => null,
        };
    }
}
