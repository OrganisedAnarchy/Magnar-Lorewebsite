@extends('admin.layout')

@section('title', $language->exists ? 'Edit language' : 'New language')

@section('content')
    <h1>{{ $language->exists ? 'Edit language' : 'New language' }}</h1>

    <div id="language-form-app" data-props="{{ json_encode([
        'action' => $language->exists ? route('admin.languages.update', $language) : route('admin.languages.store'),
        'method' => $language->exists ? 'PUT' : 'POST',
        'csrf' => csrf_token(),
        'initial' => [
            'name' => old('name', $language->name),
            'description' => old('description', $language->description),
            'phonemes' => old('phonemes', $language->phonemes ?? []),
            'phonology_notes' => old('phonology_notes', $language->phonology_notes),
            'syllables' => old('syllables', $language->syllables ?? []),
            'grammar' => old('grammar', $language->grammar),
            'writing_system' => old('writing_system', $language->writing_system),
            'words' => old('words', $language->words->map->only(['word', 'meaning', 'notes'])->values()->all()),
        ],
        'swadesh' => config('swadesh.list'),
        'errors' => $errors->getMessages(),
        'cancelUrl' => route('admin.languages.index'),
    ]) }}"></div>
@endsection
