<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoreEntryRequest;
use App\Models\LoreEntry;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoreEntryController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $category = is_string($category) && (array_key_exists($category, LoreEntry::CATEGORIES) || $category === 'uncategorized') ? $category : null;

        return view('admin.entries.index', [
            'category' => $category,
            'entries' => LoreEntry::query()
                ->when($category === 'uncategorized', fn ($query) => $query->whereNull('category'))
                ->when($category && $category !== 'uncategorized', fn ($query) => $query->where('category', $category))
                ->orderBy('category')
                ->orderBy('title')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q')));

        $entries = LoreEntry::query()
            ->where('title', 'like', "%{$term}%")
            ->orderBy('title')
            ->limit(8)
            ->get();

        return response()->json($entries->map(fn (LoreEntry $entry): array => [
            'title' => $entry->title,
            'category' => $entry->categoryLabel(),
        ])->values());
    }

    /**
     * Report, for each wikilink, whether it points at an existing entry.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'links' => ['required', 'array', 'max:200'],
            'links.*.title' => ['required', 'string', 'max:255'],
            'links.*.category' => ['nullable', 'string', 'max:255'],
        ]);

        $results = collect($data['links'])->map(
            fn (array $link): bool => LoreEntry::linkExists($link['title'], $link['category'] ?? null)
        );

        return response()->json(['results' => $results->values()]);
    }

    private function findEntry(string $title, ?string $category): ?LoreEntry
    {
        return LoreEntry::query()
            ->whereRaw('lower(title) = ?', [mb_strtolower($title)])
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('id')
            ->first();
    }

    /**
     * Find the entry a wikilink points to, creating it when it doesn't exist yet.
     */
    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $title = trim($data['title']);
        $requested = filled($data['category'] ?? null) ? LoreEntry::resolveCategory($data['category']) : null;

        if (filled($data['category'] ?? null) && $requested === null) {
            return response()->json(['message' => "Unknown category \"{$data['category']}\"."], 422);
        }

        $entry = $this->findEntry($title, $requested);

        $created = $entry === null;
        $entry ??= LoreEntry::create(['title' => $title, 'category' => $requested]);

        return response()->json([
            'created' => $created,
            'url' => route('admin.entries.edit', $entry),
        ]);
    }

    public function create(Request $request): View
    {
        $category = $request->query('category');

        return view('admin.entries.form', [
            'entry' => new LoreEntry(['category' => is_string($category) ? $category : null]),
            'linkStatuses' => LoreEntry::linkStatuses(old('description')),
        ]);
    }

    public function store(LoreEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        LoreEntry::create($data);
        SubCategory::remember($data['category'], $data['sub_category'] ?? null);

        return redirect()->route('admin.entries.index')->with('status', 'Entry created.');
    }

    public function edit(LoreEntry $entry): View
    {
        return view('admin.entries.form', [
            'entry' => $entry,
            'linkStatuses' => LoreEntry::linkStatuses(old('description', $entry->description)),
        ]);
    }

    public function update(LoreEntryRequest $request, LoreEntry $entry): RedirectResponse
    {
        $data = $request->validated();
        $entry->update($data);
        SubCategory::remember($data['category'], $data['sub_category'] ?? null);

        return redirect()->route('admin.entries.index')->with('status', 'Entry updated.');
    }

    public function destroy(LoreEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()->route('admin.entries.index')->with('status', 'Entry deleted.');
    }
}
