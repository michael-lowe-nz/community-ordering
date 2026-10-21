<?php

namespace App\Livewire;

use App\Models\Restaurant;
use App\Services\OrderGuidance;
use App\Services\OrderGuidanceCalculator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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

    /** @return LengthAwarePaginator<int, \App\Models\Order> */
    public function getOrdersProperty(): LengthAwarePaginator
    {
        $query = $this->restaurant->orders()
            ->with(['items.menuItem'])
            ->orderByDesc('ordered_at')
            ->orderByDesc('created_at');

        if ($this->partySizeFilter !== null) {
            $query->where('participant_count', $this->partySizeFilter);
        }

        if ($this->tab === 'your' && Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        return $query->paginate(10);
    }

    public function render()
    {
        return view('livewire.restaurant-view');
    }
}
