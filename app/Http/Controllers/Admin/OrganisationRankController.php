<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoreEntry;
use App\Models\OrganisationRank;
use App\Support\EntryExtras;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationRankController extends Controller
{
    public function store(Request $request, LoreEntry $entry): JsonResponse|RedirectResponse
    {
        abort_unless($entry->category === 'organisations', 404);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('organisation_ranks', 'name')->where('organisation_id', $entry->id),
            ],
            'level' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $entry->ranks()->create($data);

        return $this->respond($request, redirect()->route('admin.entries.edit', $entry), 'Rank added.', ['extras' => EntryExtras::for($entry)]);
    }

    public function destroy(Request $request, LoreEntry $entry, OrganisationRank $rank): JsonResponse|RedirectResponse
    {
        abort_unless($rank->organisation_id === $entry->id, 404);

        $rank->delete();

        return $this->respond($request, redirect()->route('admin.entries.edit', $entry), 'Rank removed.', ['extras' => EntryExtras::for($entry)]);
    }
}
