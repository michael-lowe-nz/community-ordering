<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 text-slate-900 sm:px-6">
    <header>
        <p class="text-sm text-slate-500">New order</p>
        <h1 class="text-3xl font-semibold text-slate-900">{{ $restaurant->name }}</h1>
    </header>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <label for="participant-count" class="block text-sm font-medium text-slate-700">How many people?</label>
            <input id="participant-count" type="number" min="1" max="99" wire:model="participantCount" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-500">
            @error('participantCount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <livewire:menu-item-picker :restaurant="$restaurant" :order="$order" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Order items</h2>
            <x-order-item-list :items="$items" :editable="true" />
        </div>

        @if ($showNotes)
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <label for="order-notes" class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="order-notes" wire:model="notes" rows="3" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-500"></textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @else
            <button type="button" wire:click="$set('showNotes', true)" class="cursor-pointer text-sm font-medium text-orange-700 transition hover:text-orange-800">+ Add a note</button>
        @endif

        <button type="submit" class="w-full cursor-pointer rounded-lg bg-orange-600 px-4 py-3 font-semibold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Save order</button>
    </form>
</div>