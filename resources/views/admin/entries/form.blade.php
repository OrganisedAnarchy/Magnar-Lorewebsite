@extends('admin.layout')

@section('title', $entry->exists ? 'Edit entry' : 'New entry')

@section('content')
    <h1>{{ $entry->exists ? 'Edit entry' : 'New entry' }}</h1>

    <form method="POST" class="entry-form" action="{{ $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store') }}">
        @csrf
        @if ($entry->exists)
            @method('PUT')
        @endif
        <div id="Highlevel-details" class="highlevel-details">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Choose a category…</option>
                @foreach (\App\Models\LoreEntry::CATEGORIES as $key => $info)
                    <option value="{{ $key }}" @selected(old('category', $entry->category) === $key)>{{ $info['label'] }} — {{ $info['examples'] }}</option>
                @endforeach
            </select>
            @error('category')<div class="error">{{ $message }}</div>@enderror
            @if ($entry->exists && ! $entry->category)
                <div class="hint">This entry is uncategorized — choose a category to save it.</div>
            @endif
            <label for="title">Title</label>
            <input id="title" name="title" value="{{ old('title', $entry->title) }}" required>
            @error('title')<div class="error">{{ $message }}</div>@enderror
            <fieldset id="people-dates" @if (old('category', $entry->category) !== 'people') hidden @endif>
                <legend>People &amp; beings dates</legend>
                <p class="hint">Use day-month-cycle-year. Days are 1–30, months are 1–13, and cycles are 1 or 2. Leave a date blank if it isn't recorded, tick Circa if it is approximate, or tick Unknown if it can't be known.</p>
                <div class="date-groups">
                    @foreach (['dob' => 'Date of birth (DOB)', 'dod' => 'Date of death (DOD)'] as $prefix => $label)
                        <fieldset class="date-group" data-date-group>
                            <legend>{{ $label }}</legend>
                            <div class="date-flags">
                                <label class="checkbox"><input type="checkbox" name="{{ $prefix }}_is_circa" value="1" @checked(old("{$prefix}_is_circa", $entry->{"{$prefix}_is_circa"}))> Circa</label>
                                <label class="checkbox"><input type="checkbox" name="{{ $prefix }}_is_unknown" value="1" data-date-unknown @checked(old("{$prefix}_is_unknown", $entry->{"{$prefix}_is_unknown"}))> Unknown</label>
                            </div>
                            <div class="date-fields">
                                @foreach (['day' => [1, 30], 'month' => [1, 13], 'cycle' => [1, 2], 'year' => [0, null]] as $part => $limits)
                                    @php($field = "{$prefix}_{$part}")
                                    <div class="date-field">
                                        <div class="date-control">
                                            <input
                                                id="{{ $field }}"
                                                name="{{ $field }}"
                                                type="number"
                                                min="{{ $limits[0] }}"
                                                @if ($limits[1] !== null) max="{{ $limits[1] }}" @endif
                                                step="1"
                                                placeholder=" "
                                                value="{{ old($field, $entry->{$field}) }}"
                                            >
                                            <label for="{{ $field }}">{{ ucfirst($part) }}</label>
                                        </div>
                                        @error($field)<div class="error">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </fieldset>
        </div>
        <div class="main-fields">
            <label for="description">Description
                <span class="info-tip">
                    <button type="button" aria-label="Markdown and linking help" aria-describedby="description-help">i</button>
                    <span class="info-tip-text" id="description-help" role="tooltip">Markdown. Link with [[Title]], [[Title|Alias]], [[Title@Category]] or [[Title|Alias@Category]]. Type 3+ letters for suggestions (Tab completes); Ctrl/Cmd+click a link to open it, creating it if needed.</span>
                </span>
            </label>
            <textarea id="description" name="description" data-markdown-editor data-search-url="{{ route('admin.entries.search') }}" data-resolve-url="{{ route('admin.entries.resolve') }}" data-check-url="{{ route('admin.entries.check') }}" data-link-statuses="{{ json_encode($linkStatuses) }}" data-categories="{{ json_encode(array_column(\App\Models\LoreEntry::CATEGORIES, 'label')) }}">{{ old('description', $entry->description) }}</textarea>
            @error('description')<div class="error">{{ $message }}</div>@enderror

            <button class="btn" type="submit">Save</button>
        </div>
    </form>
@endsection
