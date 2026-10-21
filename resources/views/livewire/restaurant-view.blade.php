<div class="mx-auto max-w-2xl px-4 pb-10 pt-8 sm:px-6">

    {{-- Header --}}
    <header class="mb-6">
        @php
            $cuisineIcons = [
                'chinese'     => '🥟🍚🥡',
                'japanese'    => '🍣🍱🍜',
                'italian'     => '🍝🍕🫕',
                'indian'      => '🍛🫓🥘',
                'mexican'     => '🌮🌯🥑',
                'thai'        => '🍜🥢🌶',
                'american'    => '🍔🍟🥤',
                'french'      => '🥐🫕🍷',
                'greek'       => '🫒🥙🧆',
                'korean'      => '🍖🥢🫙',
                'vietnamese'  => '🍜🥢🌿',
                'mediterranean' => '🫒🧆🥗',
            ];

            $cuisineKey = strtolower(trim($restaurant->cuisine_type ?? ''));
            $icons = $cuisineIcons[$cuisineKey] ?? '🍽';
            $iconChars = mb_str_split($icons);
        @endphp

        <div class="mb-3 flex gap-1">
            @foreach ($iconChars as $icon)
                <span class="text-3xl leading-none" aria-hidden="true">{{ $icon }}</span>
            @endforeach
        </div>

        <h1 class="text-2xl font-bold text-zinc-900">
            {{ $restaurant->name }}
            @if ($restaurant->cuisine_type || $restaurant->location)
                <span class="ml-2 text-base font-normal text-zinc-500">
                    {{ implode(' · ', array_filter([$restaurant->cuisine_type, $restaurant->location])) }}
                </span>
            @endif
        </h1>
    </header>

    {{-- How Much To Order panel --}}
    <section class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
        <div class="mb-3 flex items-baseline justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">How much to order</h2>
            <span class="text-xs text-zinc-500">based on {{ $this->orders->total() }} past {{ $this->orders->total() === 1 ? 'order' : 'orders' }}</span>
        </div>

        <div class="mb-2 flex items-center justify-between">
            <label for="party-size-slider" class="text-sm text-zinc-600">Party size</label>
            <output for="party-size-slider" class="text-xl font-bold text-zinc-900">{{ $partySize }}</output>
        </div>

        <input
            id="party-size-slider"
            type="range"
            min="1"
            max="20"
            wire:model.live="partySize"
            class="w-full cursor-pointer accent-orange-600"
            aria-label="Party size for order guidance"
        >

        <div class="mt-3 rounded-lg border border-zinc-200 bg-white px-4 py-3 text-center">
            <x-guidance-summary :guidance="$this->guidance" />
        </div>
    </section>

    {{-- Tabs --}}
    <div class="mb-4 flex overflow-hidden rounded-lg border border-zinc-200 text-sm font-medium">
        <button
            type="button"
            wire:click="selectTab('recent')"
            class="flex-1 cursor-pointer px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'recent' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
        >Recent orders</button>
        @auth
            <button
                type="button"
                wire:click="selectTab('your')"
                class="flex-1 cursor-pointer border-l border-zinc-200 px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'your' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
            >Your orders</button>
        @endauth
        <a
            href="{{ route('restaurants.orders.create.livewire', $restaurant) }}"
            class="flex-1 cursor-pointer border-l border-zinc-200 bg-white px-3 py-2 text-center text-zinc-600 transition hover:bg-zinc-50"
        >Log order</a>
    </div>

    {{-- Orders list header --}}
    <div class="mb-3 flex items-center justify-between gap-3">
        <p class="text-sm text-zinc-500">{{ $this->orders->total() }} {{ $this->orders->total() === 1 ? 'order' : 'orders' }}</p>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600">
            <span class="font-medium">Filter: party size</span>
            <select
                wire:model.live="partySizeFilter"
                class="cursor-pointer rounded-md border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 transition hover:border-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200"
            >
                <option value="">All</option>
                @foreach (range(1, 20) as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Orders --}}
    <div class="space-y-3">
        @forelse ($this->orders as $order)
            @php
                $itemNames = $order->items
                    ->map(fn ($item) => $item->menuItem?->name)
                    ->filter()
                    ->values();
                $preview = $itemNames->take(3)->implode(', ');
                $remaining = max(0, $itemNames->count() - 3);
            @endphp

            <a
                href="{{ route('orders.show', $order) }}"
                class="block rounded-xl border border-zinc-200 bg-white px-4 py-3 transition hover:border-zinc-300 hover:bg-zinc-50 hover:shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">
                            {{ $order->ordered_at?->format('j M') ?? $order->created_at->format('j M') }}
                            <span class="font-normal text-zinc-500">· party of {{ $order->participant_count }}</span>
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ $preview ?: 'No items recorded' }}@if ($remaining > 0) +{{ $remaining }}@endif
                        </p>
                    </div>
                </div>
            </a>
        @empty
            <p class="py-8 text-center text-sm text-zinc-500">No orders found.</p>
        @endforelse

        @if ($this->orders->hasPages())
            <div class="pt-2">{{ $this->orders->links() }}</div>
        @endif
    </div>

    {{-- Log a new order CTA --}}
    <div class="mt-8">
        <a
            href="{{ auth()->check() ? route('restaurants.orders.create.livewire', $restaurant) : route('login') }}"
            class="block w-full rounded-xl bg-zinc-900 px-4 py-4 text-center text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-700 focus:ring-offset-2"
        >+ Log a new order here</a>
    </div>

</div>
