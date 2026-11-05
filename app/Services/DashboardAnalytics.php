<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DashboardAnalytics
{
    public function distribution(Collection $feedback): Collection
    {
        $ratings = $feedback->map->ratingAverage()->filter(fn ($rating) => $rating !== null);

        return collect(range(1, 5))->map(function ($bucket) use ($ratings) {
            $count = $ratings->filter(fn ($rating) => max(1, min(5, (int) ceil($rating / 2))) === $bucket)->count();

            return [
                'label' => ($bucket * 2 - 1) . ' - ' . ($bucket * 2),
                'count' => $count,
                'percent' => $ratings->count() ? round($count / $ratings->count() * 100, 1) : 0,
            ];
        });
    }

    public function series(Collection $feedback, string $period): Collection
    {
        // Group the selected records, including historical periods outside the current week.
        return $feedback->filter->submitted_at->groupBy(function ($item) use ($period) {
            $date = $item->submitted_at->copy();
            if ($period === 'weekly') $date->startOfWeek();
            if ($period === 'monthly') $date->startOfMonth();
            return $date->toDateString();
        })->sortKeys()->map(function ($items, $date) use ($period) {
            $start = \Illuminate\Support\Carbon::parse($date);
            $ratings = $items->flatMap->answers->filter(fn ($answer) => $answer->type() === 'rating' && is_numeric($answer->answer));

            return [
                'date' => $date,
                'label' => $start->format($period === 'monthly' ? 'M Y' : 'M d'),
                'description' => $period === 'weekly' ? 'Week of ' . $start->format('M d, Y') : $start->format($period === 'monthly' ? 'F Y' : 'M d, Y'),
                'count' => $items->count(),
                'average' => $ratings->isEmpty() ? null : round($ratings->avg('answer'), 1),
            ];
        })->values();
    }
}
