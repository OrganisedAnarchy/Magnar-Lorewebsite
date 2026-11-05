@extends('admin.layout')

@section('title', 'Locations')

@section('content')
    <h1>Locations</h1>
    <a class="btn" href="{{ route('admin.locations.create') }}">New location</a>

    @error('delete')<p class="error">{{ $message }}</p>@enderror

    <div class="table-scroll">
        <table>
            <thead><tr><th>Name</th><th>Inside</th><th>Map</th><th>Children</th><th></th></tr></thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>{{ $location->name }}</td>
                        <td>{{ $location->parent?->name }}</td>
                        <td>{{ $location->hasMap() ? 'Yes' : 'No' }}</td>
                        <td>{{ $location->children_count }}</td>
                        <td>
                            <a href="{{ route('admin.locations.edit', $location) }}">Edit</a>
                            <form class="inline" method="POST" action="{{ route('admin.locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No locations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $locations->links() }}
@endsection
