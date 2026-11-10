<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoreEntry;
use Illuminate\View\View;

class DailyTaskController extends Controller
{
    public function index(): View
    {
        $entryCounts = LoreEntry::query()
            ->select('category')
            ->selectRaw('COUNT(*) AS entries_count')
            ->whereIn('category', array_keys(LoreEntry::CATEGORIES))
            ->groupBy('category')
            ->pluck('entries_count', 'category');

        $tasks = collect(LoreEntry::CATEGORIES)
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

        return view('admin.daily-tasks.index', ['tasks' => $tasks]);
    }
}
