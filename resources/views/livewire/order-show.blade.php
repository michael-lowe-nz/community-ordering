<div class="mx-auto max-w-md space-y-6 px-4 py-8 sm:px-6">
    <a href="{{ route('restaurants.view', $order->restaurant) }}" class="text-sm text-orange-600 transition-colors hover:text-orange-700">&larr; {{ $order->restaurant->name }}</a>

    <x-order-receipt :order="$order">
        @if ($order->notes)
            <p class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">{{ $order->notes }}</p>
        @endif
    </x-order-receipt>

    <div class="flex flex-wrap gap-3">
        @auth
            <a href="{{ route('orders.build', $order) }}" class="inline-flex items-center justify-center rounded-lg bg-orange-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Edit order</a>
            <form method="POST" action="{{ route('orders.duplicate', $order) }}">
                @csrf
                <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-800 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Use as starting point</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-800 transition hover:bg-gray-50">Log in to edit or reuse this order</a>
        @endauth
    </div>
</div>
