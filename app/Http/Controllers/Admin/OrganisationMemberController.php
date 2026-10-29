<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoreEntry;
use App\Models\OrganisationMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationMemberController extends Controller
{
    public function store(Request $request, LoreEntry $entry): RedirectResponse
    {
        abort_unless($entry->category === 'organisations', 404);

        $data = $this->validated($request, $entry, [
            'member_id' => [
                'required',
                Rule::exists('lore_entries', 'id')->where('category', 'people'),
                Rule::unique('organisation_members', 'member_id')->where('organisation_id', $entry->id),
            ],
        ]);

        $entry->members()->create($data);

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Member added.');
    }

    public function update(Request $request, LoreEntry $entry, OrganisationMember $member): RedirectResponse
    {
        abort_unless($member->organisation_id === $entry->id, 404);

        $member->update($this->validated($request, $entry));

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Member updated.');
    }

    public function destroy(LoreEntry $entry, OrganisationMember $member): RedirectResponse
    {
        abort_unless($member->organisation_id === $entry->id, 404);

        $member->delete();

        return redirect()->route('admin.entries.edit', $entry)->with('status', 'Member removed.');
    }

    /**
     * @param  array<string, mixed>  $extraRules
     * @return array<string, mixed>
     */
    private function validated(Request $request, LoreEntry $entry, array $extraRules = []): array
    {
        $request->merge([
            'is_current' => $request->boolean('is_current'),
            'join_is_circa' => $request->boolean('join_is_circa'),
        ]);

        $parts = ['join_day', 'join_month', 'join_cycle', 'join_year'];
        $partRules = fn (string $range): array => [
            'nullable', 'integer', $range, 'required_with:'.implode(',', $parts),
        ];

        return $request->validate($extraRules + [
            'organisation_rank_id' => [
                'nullable',
                Rule::exists('organisation_ranks', 'id')->where('organisation_id', $entry->id),
            ],
            'join_day' => $partRules('between:1,30'),
            'join_month' => $partRules('between:1,13'),
            'join_cycle' => $partRules('between:1,2'),
            'join_year' => $partRules('min:0'),
            'join_is_circa' => ['boolean'],
            'is_current' => ['boolean'],
        ]);
    }
}
