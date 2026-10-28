@extends('admin.layout')

@section('title', $entry->exists ? 'Edit entry' : 'New entry')

@section('content')
    <h1>{{ $entry->exists ? 'Edit entry' : 'New entry' }}</h1>

    <form method="POST" action="{{ $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store') }}">
        @csrf
        @if ($entry->exists)
            @method('PUT')
        @endif

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

        <label for="description">Description</label>
        <textarea id="description" name="description" data-markdown-editor data-search-url="{{ route('admin.entries.search') }}" data-resolve-url="{{ route('admin.entries.resolve') }}" data-check-url="{{ route('admin.entries.check') }}" data-link-statuses="{{ json_encode($linkStatuses) }}" data-categories="{{ json_encode(array_column(\App\Models\LoreEntry::CATEGORIES, 'label')) }}">{{ old('description', $entry->description) }}</textarea>
        <div class="hint">Markdown. Link with [[Title]], [[Title|Alias]], [[Title@Category]] or [[Title|Alias@Category]]. Type 3+ letters for suggestions (Tab completes); Ctrl/Cmd+click a link to open it, creating it if needed.</div>
        @error('description')<div class="error">{{ $message }}</div>@enderror

        <button class="btn" type="submit">Save</button>
    </form>
@endsection
