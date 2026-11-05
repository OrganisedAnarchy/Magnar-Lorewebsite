@extends('admin.layout')

@section('title', $book->exists ? 'Edit book' : 'New book')

@section('content')
    <h1>{{ $book->exists ? 'Edit book' : 'New book' }}</h1>

    @php
        $initial = [
            'title' => old('title', $book->title),
            'author_name' => old('author_name', $book->author?->title),
            'languages' => old('languages', $book->languages ?? []),
            'summary' => old('summary', $book->summary),
            'content' => old('content', $book->content),
            'published_is_circa' => (bool) old('published_is_circa', $book->published_is_circa),
            'published_is_unknown' => (bool) old('published_is_unknown', $book->published_is_unknown),
        ];
        foreach (['day', 'month', 'cycle', 'year'] as $part) {
            $initial["published_{$part}"] = old("published_{$part}", $book->{"published_{$part}"});
        }
    @endphp
    <div id="book-form-app" data-props="{{ json_encode([
        'action' => $book->exists ? route('admin.books.update', $book) : route('admin.books.store'),
        'method' => $book->exists ? 'PUT' : 'POST',
        'csrf' => csrf_token(),
        'draftKey' => 'draft_book_'.($book->exists ? $book->getKey() : 'new'),
        'initial' => $initial,
        'languageOptions' => $languageOptions,
        'errors' => $errors->getMessages(),
        'searchUrl' => route('admin.entries.search'),
        'cancelUrl' => route('admin.books.index'),
    ]) }}"></div>
@endsection
