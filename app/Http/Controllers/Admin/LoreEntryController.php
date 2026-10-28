<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoreEntryRequest;
use App\Models\LoreEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoreEntryController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $category = is_string($category) && array_key_exists($category, LoreEntry::CATEGORIES) ? $category : null;

        return view('admin.entries.index', [
            'category' => $category,
            'entries' => LoreEntry::query()
                ->when($category, fn ($query) => $query->where('category', $category))
                ->orderBy('category')
                ->orderBy('title')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $category = $request->query('category');

        return view('admin.entries.form', [
            'entry' => new LoreEntry(['category' => is_string($category) ? $category : null]),
        ]);
    }

    public function store(LoreEntryRequest $request): RedirectResponse
    {
        LoreEntry::create($request->validated());

        return redirect()->route('admin.entries.index')->with('status', 'Entry created.');
    }

    public function edit(LoreEntry $entry): View
    {
        return view('admin.entries.form', ['entry' => $entry]);
    }

    public function update(LoreEntryRequest $request, LoreEntry $entry): RedirectResponse
    {
        $entry->update($request->validated());

        return redirect()->route('admin.entries.index')->with('status', 'Entry updated.');
    }

    public function destroy(LoreEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()->route('admin.entries.index')->with('status', 'Entry deleted.');
    }
}
