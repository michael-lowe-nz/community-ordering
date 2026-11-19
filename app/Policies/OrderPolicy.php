<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Orders are public to read and anyone logged in can reuse one, but only the
 * person who started an order can change it.
 */
class OrderPolicy
{
    /**
     * Whether the user can edit the order's dishes, title, head count and notes.
     */
    public function update(User $user, Order $order): bool
    {
        return $order->user_id !== null && $order->user_id === $user->id;
    }
}
