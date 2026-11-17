<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderShow extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['restaurant', 'items.menuItem']);
    }

    public function useAsStartingPoint(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        $draft = Order::create([
            'restaurant_id' => $this->order->restaurant_id,
            'user_id' => Auth::id(),
            'participant_count' => $this->order->participant_count,
        ]);

        foreach ($this->order->items as $item) {
            $draft->items()->create([
                'menu_item_id' => $item->menu_item_id,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ]);
        }

        $this->redirectRoute('restaurants.orders.create.livewire', [
            'restaurant' => $this->order->restaurant,
            'draft' => $draft,
        ]);
    }

    public function render()
    {
        $items = $this->order->items->map(fn ($item): array => [
            'key' => 'order-'.$item->id,
            'name' => $item->menuItem?->name ?? '—',
            'quantity' => (int) $item->quantity,
        ]);

        return view('livewire.order-show', compact('items'));
    }
}
