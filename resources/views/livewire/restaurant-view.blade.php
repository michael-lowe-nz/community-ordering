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

        <div class="mt-4">
            @auth
                <form method="POST" action="{{ route('restaurants.orders.store', $restaurant) }}">
                    @csrf
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 sm:w-auto">Start an order</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-700 sm:w-auto">Log in to start an order</a>
            @endauth
        </div>
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
    @auth
        <div class="mb-4 flex overflow-hidden rounded-lg border border-zinc-200 text-sm font-medium">
            <button
                type="button"
                wire:click="selectTab('recent')"
                class="flex-1 cursor-pointer px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'recent' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
            >Recent orders</button>
            <button
                type="button"
                wire:click="selectTab('your')"
                class="flex-1 cursor-pointer border-l border-zinc-200 px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'your' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
            >Your orders</button>
        </div>
    @endauth

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
    @if ($this->orders->isEmpty())
        <p class="py-8 text-center text-sm text-zinc-500">No orders found.</p>
    @else
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
            @foreach ($this->orders as $order)
                <x-order-receipt :order="$order" :href="route('orders.show', $order)" :item-limit="6" wire:key="order-{{ $order->id }}" />
            @endforeach
        </div>

        @if ($this->orders->hasPages())
            <div class="pt-4">{{ $this->orders->links() }}</div>
        @endif
    @endif

</div>
