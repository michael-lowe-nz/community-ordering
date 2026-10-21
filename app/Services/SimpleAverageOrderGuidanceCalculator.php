<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use InvalidArgumentException;

final class SimpleAverageOrderGuidanceCalculator implements OrderGuidanceCalculator
{
    public function calculate(Restaurant $restaurant, int $partySize): OrderGuidance
    {
        if ($partySize < 1) {
            throw new InvalidArgumentException('Party size must be at least 1.');
        }

        $orders = $restaurant->orders()->with(['menuItems', 'items'])->get();

        if ($orders->isEmpty()) {
            return new OrderGuidance($partySize, [], 0);
        }

        /** @var list<array{counts: array<string, int>, participants: int}> $orderBreakdowns */
        $orderBreakdowns = [];

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->participant_count < 1) {
                continue;
            }

            $categoryCounts = [];

            foreach ($order->menuItems as $menuItem) {
                $category = $menuItem->section ?? 'Other';
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + (int) $menuItem->pivot->quantity;
            }

            foreach ($order->items as $orderItem) {
                $categoryCounts['Other'] = ($categoryCounts['Other'] ?? 0) + (int) $orderItem->quantity;
            }

            $orderBreakdowns[] = [
                'counts' => $categoryCounts,
                'participants' => $order->participant_count,
            ];
        }

        /** @var array<string, list<float>> $categoryRatios */
        $categoryRatios = [];

        foreach ($orderBreakdowns as $orderBreakdown) {
            foreach (array_keys($orderBreakdown['counts']) as $category) {
                $categoryRatios[$category] ??= [];
            }
        }

        foreach ($orderBreakdowns as $orderBreakdown) {
            foreach (array_keys($categoryRatios) as $category) {
                $categoryRatios[$category][] = ($orderBreakdown['counts'][$category] ?? 0) / $orderBreakdown['participants'];
            }
        }

        $categoryBreakdown = [];

        foreach ($categoryRatios as $category => $ratios) {
            $averageRatio = array_sum($ratios) / count($ratios);
            $roundedToHalf = round($averageRatio * $partySize * 2) / 2;
            $count = (int) ceil($roundedToHalf);

            if ($count > 0) {
                $categoryBreakdown[] = [
                    'category' => $category,
                    'count' => $count,
                ];
            }
        }

        usort($categoryBreakdown, fn (array $left, array $right): int => $left['category'] <=> $right['category']);

        return new OrderGuidance(
            $partySize,
            $categoryBreakdown,
            array_sum(array_column($categoryBreakdown, 'count')),
        );
    }
}
