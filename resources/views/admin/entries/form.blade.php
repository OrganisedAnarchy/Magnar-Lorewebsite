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

        <label for="title">Title</label>
        <input id="title" name="title" value="{{ old('title', $entry->title) }}" required>
        @error('title')<div class="error">{{ $message }}</div>@enderror

        <label for="description">Description</label>
        <textarea id="description" name="description" data-markdown-editor>{{ old('description', $entry->description) }}</textarea>
        <div class="hint">Markdown. Headings, bold, italics, lists and [[wikilinks]] render as you type.</div>
        @error('description')<div class="error">{{ $message }}</div>@enderror

        <button class="btn" type="submit">Save</button>
    </form>
@endsection
