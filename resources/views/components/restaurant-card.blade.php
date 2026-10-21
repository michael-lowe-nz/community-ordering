@props(['restaurant'])

@php($orderCount = $restaurant->orders_count ?? $restaurant->orders()->count())

<article {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <h2 class="text-lg font-semibold text-slate-900">{{ $restaurant->name }}</h2>
    <p class="mt-1 text-sm text-slate-600">
        {{ $restaurant->cuisine_type ?: 'Cuisine not specified' }}
        @if ($restaurant->suburb)
            <span aria-hidden="true"> · </span>{{ $restaurant->suburb }}
        @endif
    </p>
    <p class="mt-4 text-sm text-slate-500">
        {{ number_format($orderCount) }}
        {{ $orderCount === 1 ? 'order' : 'orders' }}
    </p>
</article>