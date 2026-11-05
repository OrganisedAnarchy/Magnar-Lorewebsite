<?php

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\LoreEntry;
use Illuminate\Support\Str;

/**
 * Builds the JSON state behind the Vue family and organisation sections of the entry form.
 */
class EntryExtras
{
    /**
     * @return array{category: ?string, family: ?array<string, mixed>, organisation: ?array<string, mixed>}
     */
    public static function for(LoreEntry $entry): array
    {
        return [
            'category' => $entry->category,
            'family' => $entry->category === 'people' ? self::family($entry) : null,
            'organisation' => $entry->category === 'organisations' ? self::organisation($entry) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function family(LoreEntry $entry): array
    {
        $order = array_keys(FamilyMember::RELATIONSHIPS);

        $members = $entry->familyMembers()->with('relative')->get()
            ->sortBy(fn (FamilyMember $m) => array_search($m->relationship, $order).$m->relative->title)
            ->map(fn (FamilyMember $m) => [
                'id' => $m->id,
                'name' => $m->relative->title,
                'relationship' => $m->relationship,
                'label' => $m->relationshipLabel(),
                'update_url' => route('admin.entries.family.update', [$entry, $m]),
                'destroy_url' => route('admin.entries.family.destroy', [$entry, $m]),
            ])
            ->values()
            ->all();

        return [
            'members' => $members,
            'people' => LoreEntry::query()->where('category', 'people')->whereKeyNot($entry->id)->orderBy('title')->pluck('title')->all(),
            'relationships' => collect(FamilyMember::RELATIONSHIPS)->map(fn ($info, $key) => ['value' => $key, 'label' => $info['label']])->values()->all(),
            'store_url' => route('admin.entries.family.store', $entry),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organisation(LoreEntry $entry): array
    {
        $ranks = $entry->ranks()->withCount('members')->get();

        $members = $entry->members()->with(['member', 'rank'])->get()->sortBy([
            fn ($a, $b) => $b->is_current <=> $a->is_current,
            fn ($a, $b) => ($a->rank?->level ?? PHP_INT_MAX) <=> ($b->rank?->level ?? PHP_INT_MAX),
            fn ($a, $b) => strcasecmp($a->member->title, $b->member->title),
        ])->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->member->title,
            'rank' => $m->rank?->name,
            'organisation_rank_id' => $m->organisation_rank_id,
            'join_label' => $m->joinDateLabel(),
            'join_day' => $m->join_day,
            'join_month' => $m->join_month,
            'join_cycle' => $m->join_cycle,
            'join_year' => $m->join_year,
            'join_is_circa' => $m->join_is_circa,
            'is_current' => $m->is_current,
            'update_url' => route('admin.entries.members.update', [$entry, $m]),
            'destroy_url' => route('admin.entries.members.destroy', [$entry, $m]),
        ])->values()->all();

        return [
            'ranks' => $ranks->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'level' => $r->level,
                'members_label' => $r->members_count.' '.Str::plural('member', $r->members_count),
                'destroy_url' => route('admin.entries.ranks.destroy', [$entry, $r]),
            ])->all(),
            'next_level' => ($ranks->max('level') ?? 0) + 1,
            'members' => $members,
            'people' => LoreEntry::query()->where('category', 'people')->orderBy('title')->get(['id', 'title'])
                ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->all(),
            'rank_store_url' => route('admin.entries.ranks.store', $entry),
            'member_store_url' => route('admin.entries.members.store', $entry),
        ];
    }
}
