<div class="mx-auto max-w-md space-y-6 px-4 py-8 sm:px-6">
    <a href="{{ route('restaurants.view', $order->restaurant) }}" class="text-sm text-zinc-900 transition-colors hover:text-zinc-700">&larr; {{ $order->restaurant->name }}</a>

    @if ($saved)
        <p class="rounded-lg border border-dashed border-zinc-400 px-3 py-2 text-sm text-zinc-700" role="status">Saved. Copy it to send to the group, or read it out when you call it through.</p>
    @endif

    <x-order-receipt :order="$order">
        @if ($order->notes && ! $this->canEdit)
            <p class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">{{ $order->notes }}</p>
        @endif
    </x-order-receipt>

    @if ($this->canEdit)
        <form wire:submit="saveNotes" class="space-y-2">
            <label for="order-notes" class="block text-xs font-bold uppercase tracking-widest text-zinc-500">Notes</label>
            <textarea
                id="order-notes"
                wire:model="notes"
                rows="3"
                placeholder="Dietary needs, pickup time, who's paying…"
                class="block w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-300"
            ></textarea>
            @error('notes') <p class="text-sm text-zinc-900">{{ $message }}</p> @enderror
            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">Save notes</button>
                <x-action-message on="notes-saved" />
                <span wire:loading wire:target="saveNotes" class="text-sm text-zinc-500">Saving…</span>
            </div>
        </form>
    @endif

    <div
        x-data="{
            copied: '',
            async copy(text, what) {
                await navigator.clipboard.writeText(text);
                this.copied = what;
                setTimeout(() => this.copied = '', 2000);
            },
            async share() {
                const url = @js(route('orders.show', $order));
                if (navigator.share) {
                    try { await navigator.share({ title: @js($order->title), url }); } catch (e) {}
                    return;
                }
                this.copy(url, 'link');
            },
        }"
        class="grid grid-cols-2 gap-3"
    >
        <button type="button" x-on:click="copy(@js($order->toPlainText()), 'text')" class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">
            <span x-text="copied === 'text' ? 'Copied' : 'Copy as text'">Copy as text</span>
        </button>
        <button type="button" x-on:click="share()" class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">
            <span x-text="copied === 'link' ? 'Link copied' : 'Share link'">Share link</span>
        </button>
    </div>

    <div class="flex flex-wrap gap-3">
        @auth
            @if ($this->canEdit)
                <a href="{{ route('orders.build', $order) }}" class="inline-flex flex-1 items-center justify-center rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">Edit order</a>
            @endif
            <form method="POST" action="{{ route('orders.duplicate', $order) }}" class="flex flex-1" @if ($reuse) x-data x-init="$el.submit()" @endif>
                @csrf
                <button type="submit" class="{{ $this->canEdit ? 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50' : 'bg-zinc-900 text-white hover:bg-zinc-700' }} inline-flex w-full cursor-pointer items-center justify-center rounded-lg px-4 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">Reuse this order</button>
            </form>
        @else
            <a href="{{ route('orders.reuse', $order) }}" class="inline-flex flex-1 items-center justify-center rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700">Log in to reuse this order</a>
        @endauth
    </div>
</div>
