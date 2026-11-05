@php
    $family = $entry->familyMembers()->with('relative')->get()->sortBy(fn ($m) => array_search($m->relationship, array_keys(\App\Models\FamilyMember::RELATIONSHIPS)).$m->relative->title);
    $people = \App\Models\LoreEntry::query()->where('category', 'people')->whereKeyNot($entry->id)->orderBy('title')->get(['id', 'title']);
@endphp

<section id="family-members" class="organisation-section">
    <h2>Family</h2>
    <ul>
        @forelse ($family as $member)
            <li>
                <strong>{{ $member->relationshipLabel() }}:</strong> {{ $member->relative->title }}
                <form method="POST" action="{{ route('admin.entries.family.update', [$entry, $member]) }}" style="display:inline">
                    @csrf
                    @method('PUT')
                    <select name="relationship" aria-label="Change relationship to {{ $member->relative->title }}">
                        @foreach (\App\Models\FamilyMember::RELATIONSHIPS as $key => $info)
                            <option value="{{ $key }}" @selected($member->relationship === $key)>{{ $info['label'] }}</option>
                        @endforeach
                    </select>
                    <button class="btn" type="submit">Update</button>
                </form>
                <form method="POST" action="{{ route('admin.entries.family.destroy', [$entry, $member]) }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Remove</button>
                </form>
            </li>
        @empty
            <li class="hint">No family members yet.</li>
        @endforelse
    </ul>

    <h3>Add a family member</h3>
    <p class="hint">Direct relations only e.g. Parents, Spouses, Children.</p>
    <form method="POST" action="{{ route('admin.entries.family.store', $entry) }}">
        @csrf
        <label for="family_name">Name</label>
        <input id="family_name" name="name" list="family-people" maxlength="255" value="{{ old('name') }}" required>
        <datalist id="family-people">
            @foreach ($people as $person)
                <option value="{{ $person->title }}"></option>
            @endforeach
        </datalist>
        <p class="hint">Pick an existing person, or type a new name to create them automatically.</p>
        @error('name')<div class="error">{{ $message }}</div>@enderror
        <label for="family_relationship">They are this person's…</label>
        <select id="family_relationship" name="relationship" required>
            @foreach (\App\Models\FamilyMember::RELATIONSHIPS as $key => $info)
                <option value="{{ $key }}" @selected(old('relationship') === $key)>{{ $info['label'] }}</option>
            @endforeach
        </select>
        @error('relationship')<div class="error">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Add family member</button>
    </form>
</section>
