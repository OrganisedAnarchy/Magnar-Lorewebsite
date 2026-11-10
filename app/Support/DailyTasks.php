<?php

namespace App\Support;

use App\Models\LoreEntry;
use Illuminate\Support\Collection;

class DailyTasks
{
    /**
     * @return Collection<int, array{category: string, label: string, examples: string, entries_count: int}>
     */
    public function recommendations(): Collection
    {
        $entryCounts = LoreEntry::query()
            ->select('category')
            ->selectRaw('COUNT(*) AS entries_count')
            ->whereIn('category', array_keys(LoreEntry::CATEGORIES))
            ->groupBy('category')
            ->pluck('entries_count', 'category');

        return collect(LoreEntry::CATEGORIES)
            ->map(fn (array $details, string $category): array => [
                'category' => $category,
                'label' => $details['label'],
                'examples' => $details['examples'],
                'entries_count' => (int) $entryCounts->get($category, 0),
            ])
            ->sort(function (array $left, array $right): int {
                return ($left['entries_count'] <=> $right['entries_count'])
                    ?: ($left['category'] <=> $right['category']);
            })
            ->take(3)
            ->values();
    }
}
