<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderShow extends Component
{
    public Order $order;

    public string $notes = '';

    /** Set when arriving from the builder's Done button. */
    #[Url(except: false)]
    public bool $saved = false;

    /** Set when a guest logged in to reuse this order: the page makes the copy straight away. */
    #[Url(except: false)]
    public bool $reuse = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['restaurant', 'items.menuItem']);
        $this->notes = (string) $order->notes;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return Auth::check() && Gate::allows('update', $this->order);
    }

    public function saveNotes(): void
    {
        Gate::authorize('update', $this->order);

        $validated = $this->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $this->order->update(['notes' => trim((string) $validated['notes']) ?: null]);
        $this->notes = (string) $this->order->notes;
        $this->dispatch('notes-saved');
    }

    public function render(): View
    {
        return view('livewire.order-show');
    }
}
