<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Enough Food - Restaurant Order Record Keeping</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-[#f3f2f1] text-zinc-900">
        <x-banner />

        <div class="min-h-screen bg-[#f3f2f1]">
            <x-navigation />

            @php
                $avatarClasses = [
                    'bg-rose-100 text-rose-700',
                    'bg-amber-100 text-amber-700',
                    'bg-emerald-100 text-emerald-700',
                    'bg-cyan-100 text-cyan-700',
                    'bg-violet-100 text-violet-700',
                    'bg-orange-100 text-orange-700',
                ];

                $restaurantSearchData = $restaurants
                    ->map(function ($restaurant) use ($avatarClasses) {
                        $letter = mb_substr(trim($restaurant->name), 0, 1, 'UTF-8') ?: 'R';

                        return [
                            'id' => $restaurant->id,
                            'name' => $restaurant->name,
                            'location' => $restaurant->location,
                            'orders_count' => $restaurant->orders_count,
                            'avatar_class' => $avatarClasses[$restaurant->id % count($avatarClasses)],
                            'avatar_letter' => strtoupper($letter),
                        ];
                    })
                    ->values()
                    ->all();
            @endphp

            <div class="mx-auto max-w-5xl px-4 pb-12 pt-14 sm:px-6 lg:px-8">
                <div class="flex min-h-[420px] flex-col items-center justify-center">
                    <h1 class="mb-8 text-center text-4xl font-semibold tracking-[-0.06em] text-zinc-900 sm:text-6xl">
                        find your favourite spots
                    </h1>

                    <div
                        x-data="{
                            query: '',
                            restaurants: @js($restaurantSearchData),
                            filteredRestaurants() {
                                const search = this.query.trim().toLowerCase();

                                if (!search) {
                                    return this.restaurants.slice(0, 6);
                                }

                                return this.restaurants.filter((restaurant) => {
                                    const haystack = `${restaurant.name} ${restaurant.location}`.toLowerCase();
                                    return haystack.includes(search);
                                });
                            }
                        }"
                        class="w-full max-w-3xl"
                    >
                        <div class="rounded-[32px] border border-zinc-200 bg-[#f7f6f5] p-3 shadow-[0_24px_48px_-36px_rgba(24,24,27,0.45)]">
                            <label for="restaurant-search" class="sr-only">Search for a spot</label>
                            <div class="flex items-center overflow-hidden rounded-full border border-zinc-200 bg-white shadow-inner shadow-zinc-100 transition focus-within:border-zinc-300 focus-within:ring-0">
                                <input
                                    id="restaurant-search"
                                    x-model.debounce.200ms="query"
                                    type="search"
                                    placeholder="Search for a spot"
                                    class="w-full appearance-none border-0 bg-transparent py-4 ps-6 pe-4 text-lg text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0"
                                >
                                <button
                                    type="button"
                                    class="mr-1 inline-flex shrink-0 items-center justify-center rounded-full bg-[#e74a69] px-5 py-3 text-sm font-medium text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.28)] transition hover:bg-[#dc3d5a] focus:outline-none focus:ring-0"
                                >
                                    Find
                                </button>
                            </div>
                        </div>

                        <div class="mt-8 space-y-3">
                            <template x-for="restaurant in filteredRestaurants()" :key="restaurant.id">
                                <a
                                    :href="`/restaurant/${restaurant.id}`"
                                    class="group block rounded-2xl border border-zinc-200 bg-white px-3 py-3 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-semibold" :class="restaurant.avatar_class"
                                                x-text="restaurant.avatar_letter"
                                            ></div>

                                            <div class="min-w-0">
                                                <div class="truncate text-base font-medium text-zinc-900" x-text="restaurant.name"></div>
                                                <div class="mt-0.5 truncate text-sm text-zinc-500" x-text="restaurant.location"></div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-600" x-text="`${restaurant.orders_count} orders`"></span>
                                            <svg class="h-4 w-4 text-zinc-400 transition group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M7 17L17 7"></path>
                                                <path d="M7 7h10v10"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </template>

                            <div
                                x-show="filteredRestaurants().length === 0"
                                x-cloak
                                class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 px-4 py-8 text-center text-sm text-zinc-500"
                            >
                                No restaurants match your search.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>