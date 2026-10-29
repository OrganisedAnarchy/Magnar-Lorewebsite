@php
    $ranks = $entry->ranks()->withCount('members')->get();
    $members = $entry->members()->with(['member', 'rank'])->get()->sortBy([
        fn ($a, $b) => $b->is_current <=> $a->is_current,
        fn ($a, $b) => ($a->rank?->level ?? PHP_INT_MAX) <=> ($b->rank?->level ?? PHP_INT_MAX),
        fn ($a, $b) => strcasecmp($a->member->title, $b->member->title),
    ]);
    $people = \App\Models\LoreEntry::query()->where('category', 'people')->orderBy('title')->get(['id', 'title']);
@endphp

<section id="organisation-ranks" class="organisation-section">
    <h2>Rank hierarchy</h2>
    <p class="hint">Level 1 is the highest rank; higher numbers sit lower in the hierarchy.</p>
    <ul>
        @forelse ($ranks as $rank)
            <li>
                <strong>{{ $rank->level }}.</strong> {{ $rank->name }}
                <span class="hint">({{ $rank->members_count }} {{ \Illuminate\Support\Str::plural('member', $rank->members_count) }})</span>
                <form method="POST" action="{{ route('admin.entries.ranks.destroy', [$entry, $rank]) }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Remove</button>
                </form>
            </li>
        @empty
            <li class="hint">No ranks defined yet.</li>
        @endforelse
    </ul>
    <form method="POST" action="{{ route('admin.entries.ranks.store', $entry) }}">
        @csrf
        <label for="rank_name">Rank name</label>
        <input id="rank_name" name="name" maxlength="100" value="{{ old('name') }}" required>
        @error('name')<div class="error">{{ $message }}</div>@enderror
        <label for="rank_level">Level</label>
        <input id="rank_level" name="level" type="number" min="1" max="65535" value="{{ old('level', ($ranks->max('level') ?? 0) + 1) }}" required>
        @error('level')<div class="error">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Add rank</button>
    </form>
</section>

<section id="organisation-members" class="organisation-section">
    <h2>Known members</h2>
    <table>
        <thead>
            <tr><th>Member</th><th>Rank</th><th>Joined (day-month-cycle-year)</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($members as $membership)
                <tr>
                    <td>{{ $membership->member->title }}</td>
                    <td>{{ $membership->rank?->name ?? '—' }}</td>
                    <td>{{ $membership->joinDateLabel() ?? 'Unknown' }}</td>
                    <td>{{ $membership->is_current ? 'Current' : 'Former' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.entries.members.update', [$entry, $membership]) }}">
                            @csrf
                            @method('PUT')
                            <select name="organisation_rank_id" aria-label="Rank">
                                <option value="">No rank</option>
                                @foreach ($ranks as $rank)
                                    <option value="{{ $rank->id }}" @selected($membership->organisation_rank_id === $rank->id)>{{ $rank->name }}</option>
                                @endforeach
                            </select>
                            @foreach (['day' => 1, 'month' => 1, 'cycle' => 1, 'year' => 0] as $part => $min)
                                <input name="join_{{ $part }}" type="number" min="{{ $min }}" @if ($part === 'day') max="30" @elseif ($part === 'month') max="13" @elseif ($part === 'cycle') max="2" @endif placeholder="{{ ucfirst($part) }}" aria-label="Join {{ $part }}" value="{{ $membership->{'join_'.$part} }}">
                            @endforeach
                            <label class="checkbox"><input type="checkbox" name="join_is_circa" value="1" @checked($membership->join_is_circa)> Circa</label>
                            <label class="checkbox"><input type="checkbox" name="is_current" value="1" @checked($membership->is_current)> Current member</label>
                            <button class="btn" type="submit">Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.entries.members.destroy', [$entry, $membership]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="hint">No known members yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Add a member</h3>
    <form method="POST" action="{{ route('admin.entries.members.store', $entry) }}">
        @csrf
        <label for="member_id">Person</label>
        <select id="member_id" name="member_id" required>
            <option value="">Choose a person…</option>
            @foreach ($people as $person)
                <option value="{{ $person->id }}" @selected((int) old('member_id') === $person->id)>{{ $person->title }}</option>
            @endforeach
        </select>
        @error('member_id')<div class="error">{{ $message }}</div>@enderror
        <label for="new_member_rank">Rank</label>
        <select id="new_member_rank" name="organisation_rank_id">
            <option value="">No rank</option>
            @foreach ($ranks as $rank)
                <option value="{{ $rank->id }}" @selected((int) old('organisation_rank_id') === $rank->id)>{{ $rank->name }}</option>
            @endforeach
        </select>
        @error('organisation_rank_id')<div class="error">{{ $message }}</div>@enderror
        <fieldset>
            <legend>Join date <span class="hint">(optional; days 1–30, months 1–13, cycles 1–2)</span></legend>
            @foreach (['day' => 1, 'month' => 1, 'cycle' => 1, 'year' => 0] as $part => $min)
                <input name="join_{{ $part }}" type="number" min="{{ $min }}" @if ($part === 'day') max="30" @elseif ($part === 'month') max="13" @elseif ($part === 'cycle') max="2" @endif placeholder="{{ ucfirst($part) }}" aria-label="Join {{ $part }}" value="{{ old('join_'.$part) }}">
                @error('join_'.$part)<div class="error">{{ $message }}</div>@enderror
            @endforeach
            <label class="checkbox"><input type="checkbox" name="join_is_circa" value="1" @checked(old('join_is_circa'))> Circa</label>
        </fieldset>
        <label class="checkbox"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', true))> Current member (untick for a former member)</label>
        <button class="btn" type="submit">Add member</button>
    </form>
</section>
