<?php

use App\Models\OrderItem;
use Livewire\Component;
use App\Models\Restaurant;

new class extends Component{
    public string $search = '';

    public array $suggestions = [];

    public function updatedSearch(string $value): void
    {
        $term = trim($value);

        if ($term === '') {
            $this->suggestions = [];
            return;
        }

        $currentResturant = request()->route('restaurant');

        $this->suggestions = $currentResturant->orderItems()
            ->where('name', 'like', "%{$term}%")
            ->distinct()
            ->limit(8)
            ->pluck('name')
            ->all();
    }

    public function chooseSuggestion(string $name): void
    {
        $this->search = $name;
        $this->suggestions = [];
    }

    public function currentRestaurant(): Restaurant
    {
        return request()->route('restaurant');
        
    }

    public function save(): void
    {
    }

};
?>

<div>
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search order items..." autocomplete="off">
    <p>Current restauraunt: {{ $this->currentRestaurant()->name }}</p>
    <p>{{ $search }}</p>
    <ul>
        @foreach ($suggestions as $suggestion)
            <li wire:key="suggestion-{{ md5($suggestion) }}">
                <button type="button" wire:click='chooseSuggestion(@js($suggestion))'>{{ $suggestion }}</button>
            </li>
        @endforeach
    </ul>
</div>