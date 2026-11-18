<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\View\View;
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

    public function render(): View
    {
        return view('livewire.order-show');
    }
}
