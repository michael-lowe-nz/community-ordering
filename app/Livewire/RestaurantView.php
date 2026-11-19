<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RestaurantView extends Component
{
    use WithPagination;

    public Restaurant $restaurant;

    /** Opens the "Start an order" sheet on load, e.g. when coming back from logging in. */
    #[Url(except: false)]
    public bool $start = false;

    /** @return LengthAwarePaginator<int, Order> */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->restaurant->orders()
            ->has('items')
            ->with(['items.menuItem'])
            ->orderByRaw('coalesce(ordered_at, created_at) desc')
            ->orderByDesc('id')
            ->paginate(12);
    }

    /**
     * The signed-in user's most recent order here that has dishes in it,
     * offered as a starting point for the next one.
     */
    #[Computed]
    public function lastOrder(): ?Order
    {
        if (! Auth::check()) {
            return null;
        }

        return $this->restaurant->orders()
            ->where('user_id', Auth::id())
            ->has('items')
            ->withCount('items')
            ->orderByRaw('coalesce(ordered_at, created_at) desc')
            ->orderByDesc('id')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.restaurant-view');
    }
}
