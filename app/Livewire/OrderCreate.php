<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderCreate extends Component
{
    public Restaurant $restaurant;

    public Order $order;

    public int $participantCount = 4;

    public bool $showNotes = false;

    public string $notes = '';

    public function mount(Restaurant $restaurant, ?Order $order = null): void
    {
        $this->restaurant = $restaurant;
        $draftId = request()->integer('draft');

        $existingDraft = $draftId > 0
            ? Order::query()
                ->whereKey($draftId)
                ->where('restaurant_id', $restaurant->id)
                ->where('user_id', Auth::id())
                ->whereNull('ordered_at')
                ->first()
            : null;

        if ($order) {
            // Order explicitly provided (e.g. via route model binding or test).
            $this->order = $order;
        } elseif ($existingDraft) {
            $this->order = $existingDraft;
        } else {
            // Create a new draft and redirect to pin its ID in the URL.
            $this->order = Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => Auth::id(),
                'participant_count' => $this->participantCount,
            ]);

            $this->redirectRoute('restaurants.orders.create.livewire', [
                'restaurant' => $restaurant,
                'draft' => $this->order->id,
            ]);

            return;
        }

        $this->participantCount = (int) ($this->order->participant_count ?: 4);
        $this->notes = (string) ($this->order->notes ?? '');
        $this->showNotes = filled($this->notes);
    }

    #[On('item-added')]
    public function refreshItems(): void
    {
        $this->order->refresh();
    }

    #[On('order-item-increment')]
    public function incrementOrderItem(int $itemId): void
    {
        $this->order->items()->whereKey($itemId)->increment('quantity');
    }

    #[On('order-item-decrement')]
    public function decrementOrderItem(int $itemId): void
    {
        $item = $this->order->items()->find($itemId);

        if (! $item) {
            return;
        }

        $item->quantity > 1 ? $item->decrement('quantity') : $item->delete();
    }

    #[On('order-item-remove')]
    public function removeOrderItem(int $itemId): void
    {
        $this->order->items()->whereKey($itemId)->delete();
    }

    public function save(): void
    {
        $this->validate([
            'participantCount' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->order->forceFill([
            'participant_count' => $this->participantCount,
            'notes' => $this->notes,
            'ordered_at' => now(),
        ])->save();

        $this->redirectRoute('orders.show', ['order' => $this->order]);
    }

    public function render()
    {
        $items = $this->order->items()->with('menuItem')->orderBy('created_at')->get()
            ->map(fn ($item): array => [
                'id' => $item->id,
                'key' => 'order-'.$item->id,
                'name' => $item->menuItem?->name ?? '—',
                'quantity' => (int) $item->quantity,
                'type' => 'order',
            ]);

        return view('livewire.order-create', compact('items'));
    }
}
