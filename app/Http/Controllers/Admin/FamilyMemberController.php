<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Models\LoreEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FamilyMemberController extends Controller
{
    public function store(Request $request, LoreEntry $entry): RedirectResponse
    {
        abort_unless($entry->category === 'people', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', Rule::in(array_keys(FamilyMember::RELATIONSHIPS))],
        ]);

        $name = trim($data['name']);

        $relative = LoreEntry::query()
            ->where('category', 'people')
            ->whereRaw('lower(title) = ?', [mb_strtolower($name)])
            ->first();

        if ($relative?->is($entry)) {
            return back()->withErrors(['name' => 'A person cannot be their own relative.'])->withInput();
        }

        if ($relative && $entry->familyMembers()->where('relative_id', $relative->id)->exists()) {
            return back()->withErrors(['name' => "{$relative->title} is already listed as a family member."])->withInput();
        }

        DB::transaction(function () use ($entry, $relative, $name, $data): void {
            $relative ??= LoreEntry::create(['category' => 'people', 'title' => $name]);

            $entry->familyMembers()->create([
                'relative_id' => $relative->id,
                'relationship' => $data['relationship'],
            ]);

            $relative->familyMembers()->create([
                'relative_id' => $entry->id,
                'relationship' => FamilyMember::RELATIONSHIPS[$data['relationship']]['inverse'],
            ]);
        });

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Family member added.');
    }

    public function destroy(LoreEntry $entry, FamilyMember $member): RedirectResponse
    {
        abort_unless($member->person_id === $entry->id, 404);

        DB::transaction(function () use ($entry, $member): void {
            FamilyMember::query()
                ->where('person_id', $member->relative_id)
                ->where('relative_id', $entry->id)
                ->delete();
            $member->delete();
        });

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Family member removed.');
    }
}
