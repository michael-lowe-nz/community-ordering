<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderGuidance;
use App\Services\OrderGuidanceCalculator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RestaurantView extends Component
{
    use WithPagination;

    public Restaurant $restaurant;

    public int $partySize = 4;

    public ?int $partySizeFilter = null;

    public string $tab = 'recent';

    public function updatedPartySizeFilter(): void
    {
        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        if ($tab === 'your' && ! Auth::check()) {
            $this->tab = 'recent';

            return;
        }

        $this->tab = in_array($tab, ['recent', 'your'], true) ? $tab : 'recent';
        $this->resetPage();
    }

    public function getGuidanceProperty(): OrderGuidance
    {
        return app(OrderGuidanceCalculator::class)->calculate($this->restaurant, $this->partySize);
    }

    /** @return LengthAwarePaginator<int, Order> */
    public function getOrdersProperty(): LengthAwarePaginator
    {
        $query = $this->restaurant->orders()
            ->has('items')
            ->with(['items.menuItem'])
            ->orderByRaw('coalesce(ordered_at, created_at) desc')
            ->orderByDesc('id');

        if ($this->partySizeFilter !== null) {
            $query->where('participant_count', $this->partySizeFilter);
        }

        if ($this->tab === 'your' && Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        return $query->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.restaurant-view');
    }
}
