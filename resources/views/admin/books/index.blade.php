@extends('admin.layout')

@section('title', 'In-world books')

@section('content')
    <h1>In-world books</h1>
    <a class="btn" href="{{ route('admin.books.create') }}">New book</a>

    <div class="table-scroll">
        <table>
            <thead><tr><th>Title</th><th>Author</th><th>Languages</th><th></th></tr></thead>
            <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>{{ $book->title }}</td>
                        <td>{{ $book->author?->title }}</td>
                        <td>{{ implode(', ', $book->languages ?? []) }}</td>
                        <td>
                            <a href="{{ route('admin.books.edit', $book) }}">Edit</a>
                            <form class="inline" method="POST" action="{{ route('admin.books.destroy', $book) }}" onsubmit="return confirm('Delete this book?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No books yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $books->links() }}
@endsection
