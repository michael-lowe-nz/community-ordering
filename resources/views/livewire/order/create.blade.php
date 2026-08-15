<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @if ($editingTitle)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input
                    type="text"
                    wire:model="orderTitle"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-lg font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-100"
                    placeholder="{{ $order->title }}"
                >
                <div class="flex gap-2">
                    <button type="button" wire:click="saveTitle" class="rounded-lg bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-700">Save</button>
                    <button type="button" wire:click="$set('editingTitle', false)" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-2xl font-semibold text-slate-900">{{ $orderTitle }}</h3>
                <button type="button" wire:click="startTitleEdit" class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" aria-label="Edit title">✎</button>
            </div>
        @endif
    </div>

    @if (! empty($availableItems))
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Quick add</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($availableItems as $item)
                    <button
                        type="button"
                        wire:click="quickAddItem(@js($item['name']), @js($item['price']))"
                        class="flex flex-col rounded-xl border border-slate-200 bg-slate-50 p-3 text-left transition hover:border-orange-400 hover:bg-orange-50"
                    >
                        <p class="truncate font-medium text-slate-900">{{ $item['name'] }}</p>
                        @if ($item['price'])
                            <p class="mt-1 text-sm text-slate-600">${{ number_format($item['price'], 2) }}</p>
                        @else
                            <p class="mt-1 text-sm text-slate-400">Price TBD</p>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit.prevent="addItem" class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5">
                <label for="item-search" class="mb-2 block text-sm font-medium text-slate-700">Search menu item</label>
                <div class="relative">
                    <input
                        id="item-search"
                        type="text"
                        wire:model.live.debounce.200ms="query"
                        wire:keydown.enter.prevent="addItem"
                        autocomplete="off"
                        autofocus
                        placeholder="Search for an item or type a new one"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-100"
                    >

                    @if (! empty($suggestions))
                        <div class="absolute left-0 right-0 z-20 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            @foreach ($suggestions as $suggestion)
                                <button
                                    type="button"
                                    wire:click="chooseSuggestion(@js($suggestion))"
                                    class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-orange-50 hover:text-orange-700 last:border-b-0"
                                >
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @error('query')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                <div>
                    <label for="item-quantity" class="mb-2 block text-sm font-medium text-slate-700">Quantity</label>
                    <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50">
                        <button type="button" wire:click="decrementQuantity" class="flex h-12 w-12 items-center justify-center text-xl text-slate-600 transition hover:bg-slate-200">−</button>
                        <input
                            id="item-quantity"
                            type="number"
                            min="1"
                            max="99"
                            wire:model="quantity"
                            class="w-full border-0 bg-transparent px-4 py-3 text-center text-base font-medium text-slate-900 outline-none"
                        >
                        <button type="button" wire:click="incrementQuantity" class="flex h-12 w-12 items-center justify-center text-xl text-slate-600 transition hover:bg-slate-200">+</button>
                    </div>
                    @error('quantity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex h-12 items-center justify-center rounded-xl bg-orange-600 px-6 text-base font-semibold text-white shadow-sm transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-200"
                >
                    Add to order
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Order summary</h3>
                <span class="text-sm text-slate-500">{{ $order->items()->count() }} item{{ $order->items()->count() === 1 ? '' : 's' }}</span>
            </div>

            @forelse ($order->items()->orderBy('created_at')->get() as $item)
                <div x-data="{ open: false }" class="relative border-b border-slate-100 py-3 last:border-b-0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-slate-800">{{ $item->name }}</p>
                            <p class="text-sm text-slate-500">{{ $item->quantity }} × {{ $item->price ? '$' . number_format($item->price, 2) : 'Price TBD' }}</p>
                        </div>

                        <div class="relative flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700">{{ $item->quantity }}</span>
                            <button type="button" @click="open = !open" class="flex h-8 w-8 items-center justify-center rounded-full text-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Open item options">
                                ⋯
                            </button>
                        </div>
                    </div>

                    <div x-show="open" x-transition @click.away="open = false" class="absolute right-0 top-12 z-20 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <button type="button" wire:click="beginEditItem({{ $item->id }})" @click="open = false" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">Edit</button>
                        <button type="button" wire:click="incrementItemQuantity({{ $item->id }})" @click="open = false" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">Add 1</button>
                        <button type="button" wire:click="decrementItemQuantity({{ $item->id }})" @click="open = false" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">Remove 1</button>
                        <button type="button" wire:click="removeItem({{ $item->id }})" @click="open = false" class="block w-full px-4 py-3 text-left text-sm text-red-600 transition hover:bg-red-50">Remove</button>
                    </div>

                    @if ($editingItemId === $item->id)
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <input
                                    type="text"
                                    wire:model="itemDraftName"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                                >
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    wire:model="itemDraftQuantity"
                                    class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                                >
                            </div>
                            @error('itemDraftName')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex gap-2">
                                <button type="button" wire:click="saveEditedItem({{ $item->id }})" class="rounded-lg bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-700">Save</button>
                                <button type="button" wire:click="$set('editingItemId', null)" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    No items added yet.
                </div>
            @endforelse
        </div>
    </form>
</div>
