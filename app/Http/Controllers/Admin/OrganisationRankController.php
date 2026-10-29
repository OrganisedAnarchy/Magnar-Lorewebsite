<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoreEntry;
use App\Models\OrganisationRank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationRankController extends Controller
{
    public function store(Request $request, LoreEntry $entry): RedirectResponse
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

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Rank added.');
    }

    public function destroy(LoreEntry $entry, OrganisationRank $rank): RedirectResponse
    {
        abort_unless($rank->organisation_id === $entry->id, 404);

        $rank->delete();

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Rank removed.');
    }
}
