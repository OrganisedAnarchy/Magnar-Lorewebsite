@extends('admin.layout')

@section('title', 'Daily Tasks')

@section('content')
    <h1>Daily Tasks</h1>
    <p class="hint">These three lore categories have the fewest entries. Add to them to help balance the setting.</p>

    @foreach ($tasks as $task)
        <section class="panel">
            <h2>{{ $task['label'] }}</h2>
            <p>{{ $task['examples'] }}</p>
            <p class="hint">{{ $task['entries_count'] }} {{ \Illuminate\Support\Str::plural('entry', $task['entries_count']) }} currently in this category.</p>
            <a class="btn" href="{{ route('admin.entries.create', ['category' => $task['category']]) }}">Add an entry</a>
        </section>
    @endforeach
@endsection
