<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Models\LoreEntry;
use App\Support\EntryExtras;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FamilyMemberController extends Controller
{
    public function store(Request $request, LoreEntry $entry): JsonResponse|RedirectResponse
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
            throw ValidationException::withMessages(['name' => 'A person cannot be their own relative.']);
        }

        if ($relative && $entry->familyMembers()->where('relative_id', $relative->id)->exists()) {
            throw ValidationException::withMessages(['name' => "{$relative->title} is already listed as a family member."]);
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

        return $this->respond($request, redirect()->route('admin.entries.edit', $entry), 'Family member added.', ['extras' => EntryExtras::for($entry)]);
    }

    public function update(Request $request, LoreEntry $entry, FamilyMember $member): JsonResponse|RedirectResponse
    {
        abort_unless($member->person_id === $entry->id, 404);

        $data = $request->validate([
            'relationship' => ['required', Rule::in(array_keys(FamilyMember::RELATIONSHIPS))],
        ]);

        DB::transaction(function () use ($entry, $member, $data): void {
            $member->update(['relationship' => $data['relationship']]);

            FamilyMember::query()
                ->where('person_id', $member->relative_id)
                ->where('relative_id', $entry->id)
                ->update(['relationship' => FamilyMember::RELATIONSHIPS[$data['relationship']]['inverse']]);
        });

        return $this->respond($request, redirect()->route('admin.entries.edit', $entry), 'Family relationship updated.', ['extras' => EntryExtras::for($entry)]);
    }

    public function destroy(Request $request, LoreEntry $entry, FamilyMember $member): JsonResponse|RedirectResponse
    {
        abort_unless($member->person_id === $entry->id, 404);

        DB::transaction(function () use ($entry, $member): void {
            FamilyMember::query()
                ->where('person_id', $member->relative_id)
                ->where('relative_id', $entry->id)
                ->delete();
            $member->delete();
        });

        return $this->respond($request, redirect()->route('admin.entries.edit', $entry), 'Family member removed.', ['extras' => EntryExtras::for($entry)]);
    }
}
