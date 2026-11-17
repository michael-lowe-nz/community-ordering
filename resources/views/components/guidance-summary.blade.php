@props(['guidance'])

@if (empty($guidance->categoryBreakdown))
    <p class="text-sm text-zinc-500">Not enough data yet to suggest quantities.</p>
@else
    <p class="text-base font-semibold text-zinc-900">
        ≈ @foreach ($guidance->categoryBreakdown as $index => $breakdown)@if ($index > 0)<span class="font-normal text-zinc-400">, </span>@endif{{ $breakdown['count'] }} {{ strtolower($breakdown['category']) }}@endforeach
    </p>
@endif
