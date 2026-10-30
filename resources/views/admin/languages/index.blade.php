@extends('admin.layout')

@section('title', 'Languages')

@section('content')
    <h1>Languages</h1>
    <a class="btn" href="{{ route('admin.languages.create') }}">New language</a>

    <table>
        <thead><tr><th>Name</th><th>Words</th><th></th></tr></thead>
        <tbody>
            @forelse ($languages as $language)
                <tr>
                    <td>{{ $language->name }}</td>
                    <td>{{ $language->words_count }}</td>
                    <td>
                        <a href="{{ route('admin.languages.edit', $language) }}">Edit</a>
                        <form class="inline" method="POST" action="{{ route('admin.languages.destroy', $language) }}" onsubmit="return confirm('Delete this language?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No languages yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $languages->links() }}
@endsection
