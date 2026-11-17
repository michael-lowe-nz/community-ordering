<div
    class="space-y-4 text-slate-900"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    {{-- Search input --}}
    <div class="relative">
        <label for="menu-item-search" class="mb-2 block text-sm font-medium text-slate-700">Search menu items</label>

        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
                </svg>
            </div>
            <input
                id="menu-item-search"
                type="text"
                wire:model.live.debounce.200ms="query"
                wire:keydown.enter.prevent="selectFirstOrAddAdHoc"
                @focus="open = true"
                @input="open = true"
                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-slate-900 placeholder:text-slate-400 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/20"
                placeholder="Start typing to search..."
                autocomplete="off"
            >
        </div>

        {{-- Dropdown --}}
        @if (trim($query) !== '')
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
            >
                @if ($filteredItems->isNotEmpty())
                    <ul class="max-h-60 overflow-y-auto divide-y divide-slate-100">
                        @foreach ($filteredItems as $item)
                            <li>
                                <button
                                    type="button"
                                    wire:click="selectMenuItem({{ $item->id }})"
                                    @click="open = false"
                                    class="flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-800 transition hover:bg-slate-50"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ $item->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Create option: only when no match AND query >= 2 chars --}}
                @if ($filteredItems->isEmpty() && Str::length(trim($query)) >= 2)
                    <button
                        type="button"
                        wire:click="addAdHocItem"
                        @click="open = false"
                        class="flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-left text-sm font-medium text-orange-700 transition hover:bg-orange-50"
                    >
                        <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create "<span class="font-semibold">{{ trim($query) }}</span>"
                    </button>
                @elseif ($filteredItems->isEmpty() && Str::length(trim($query)) < 2)
                    <p class="px-3 py-2.5 text-sm text-slate-400">Keep typing to search…</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Frequently ordered pills (shown when input is empty) --}}
    @if ($frequentlyOrderedItems->isNotEmpty() && trim($query) === '')
        <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">
                {{ $frequentlyOrderedItems->contains(fn ($i) => ($i->restaurant_order_count ?? 0) > 0) ? 'Frequently ordered here' : 'Menu items' }}
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($frequentlyOrderedItems as $item)
                    <button
                        type="button"
                        wire:click="selectMenuItem({{ $item->id }})"
                        class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm text-slate-700 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-800 cursor-pointer"
                    >
                        {{ $item->name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
