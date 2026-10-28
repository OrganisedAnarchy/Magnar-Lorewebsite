@extends('admin.layout')

@section('title', 'Lore')

@section('content')
    <h1>Lore</h1>
    <a class="btn" href="{{ route('admin.entries.create', ['category' => $category]) }}">New entry</a>

    <nav class="filters" aria-label="Categories">
        <a href="{{ route('admin.entries.index') }}" @class(['active' => ! $category])>All</a>
        <a href="{{ route('admin.entries.index', ['category' => 'uncategorized']) }}" @class(['active' => $category === 'uncategorized'])>Uncategorized</a>
        @foreach (\App\Models\LoreEntry::CATEGORIES as $key => $info)
            <a href="{{ route('admin.entries.index', ['category' => $key]) }}" title="{{ $info['examples'] }}" @class(['active' => $category === $key])>{{ $info['label'] }}</a>
        @endforeach
    </nav>

    <table>
        <thead><tr><th>Title</th><th>Category</th><th></th></tr></thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry->title }}</td>
                    <td>{{ $entry->categoryLabel() }}</td>
                    <td>
                        <a href="{{ route('admin.entries.edit', $entry) }}">Edit</a>
                        <form class="inline" method="POST" action="{{ route('admin.entries.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No entries yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
@endsection
