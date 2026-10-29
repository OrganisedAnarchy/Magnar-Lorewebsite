@extends('admin.layout')

@section('title', $entry->exists ? 'Edit entry' : 'New entry')

@section('content')
    <h1>{{ $entry->exists ? 'Edit entry' : 'New entry' }}</h1>

    @php
        $fields = ['category', 'sub_category', 'title', 'description'];
        foreach (['dob', 'dod'] as $prefix) {
            foreach (['day', 'month', 'cycle', 'year', 'is_circa', 'is_unknown'] as $part) {
                $fields[] = "{$prefix}_{$part}";
            }
        }
        $initial = collect($fields)->mapWithKeys(fn ($field) => [$field => old($field, $entry->{$field})])->all();
    @endphp
    <div id="entry-form-app" data-props="{{ json_encode([
        'action' => $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store'),
        'method' => $entry->exists ? 'PUT' : 'POST',
        'csrf' => csrf_token(),
        'draftKey' => 'draft_entry_'.($entry->exists ? $entry->getKey() : 'new'),
        'initial' => $initial,
        'errors' => $errors->getMessages(),
        'categories' => \App\Models\LoreEntry::CATEGORIES,
        'subCategories' => \App\Models\SubCategory::grouped(),
        'urls' => ['search' => route('admin.entries.search'), 'resolve' => route('admin.entries.resolve'), 'check' => route('admin.entries.check')],
        'linkStatuses' => $linkStatuses,
        'uncategorized' => $entry->exists && ! $entry->category,
    ]) }}"></div>
    @if ($entry->exists && $entry->category === 'organisations')
        @include('admin.entries._organisation')
    @endif
@endsection
