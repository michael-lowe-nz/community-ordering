@props(['items', 'editable' => false])

<ul {{ $attributes->merge(['class' => 'divide-y divide-slate-200']) }}>
    @forelse ($items as $item)
        <li wire:key="order-item-{{ data_get($item, 'key', data_get($item, 'id', $loop->index)) }}" class="flex items-center justify-between gap-4 py-3">
            <span class="font-medium text-slate-900">{{ data_get($item, 'name') }}</span>
            @if ($editable)
                <span class="flex items-center gap-1">
                    <button type="button" class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-slate-200 text-sm text-slate-600 transition hover:border-slate-300 hover:bg-slate-100" wire:click="$dispatch('order-item-decrement', { itemId: {{ data_get($item, 'id', 0) }}, itemType: '{{ data_get($item, 'type', 'order') }}' })" aria-label="Decrease {{ data_get($item, 'name') }} quantity">−</button>
                    <span class="w-6 text-center text-sm text-slate-900">{{ data_get($item, 'quantity') }}</span>
                    <button type="button" class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-slate-200 text-sm text-slate-600 transition hover:border-slate-300 hover:bg-slate-100" wire:click="$dispatch('order-item-increment', { itemId: {{ data_get($item, 'id', 0) }}, itemType: '{{ data_get($item, 'type', 'order') }}' })" aria-label="Increase {{ data_get($item, 'name') }} quantity">+</button>
                    <button type="button" class="ml-1 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-red-100 text-sm text-red-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600" wire:click="$dispatch('order-item-remove', { itemId: {{ data_get($item, 'id', 0) }}, itemType: '{{ data_get($item, 'type', 'order') }}' })" aria-label="Remove {{ data_get($item, 'name') }}">×</button>
                </span>
            @else
                <span class="text-sm text-slate-600">{{ data_get($item, 'quantity') }}</span>
            @endif
        </li>
    @empty
        <li class="py-4 text-sm text-slate-500">No items added yet.</li>
    @endforelse
</ul>