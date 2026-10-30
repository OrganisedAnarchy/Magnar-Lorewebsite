<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Book;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('admin.books.index', [
            'books' => Book::query()->with('author')->orderBy('title')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.books.form', [
            'book' => new Book(['languages' => [Book::DEFAULT_LANGUAGE]]),
            'languageOptions' => $this->languageOptions(),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $this->persist(new Book, $request->validated());

        return redirect()->route('admin.books.index')->with('status', 'Book created.');
    }

    public function edit(Book $book): View
    {
        return view('admin.books.form', [
            'book' => $book->load('author'),
            'languageOptions' => $this->languageOptions(),
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->persist($book, $request->validated());

        return redirect()->route('admin.books.index')->with('status', 'Book updated.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return redirect()->route('admin.books.index')->with('status', 'Book deleted.');
    }

    /**
     * @return list<string>
     */
    private function languageOptions(): array
    {
        return Language::query()->orderBy('name')->pluck('name')->prepend(Book::DEFAULT_LANGUAGE)->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Book $book, array $data): void
    {
        $authorName = trim((string) ($data['author_name'] ?? ''));
        unset($data['author_name']);

        $data['languages'] = collect($data['languages'] ?? [])
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values()
            ->all();

        DB::transaction(function () use ($book, $data, $authorName): void {
            $data['author_id'] = $authorName === '' ? null : Book::resolveAuthor($authorName, $data['title'])->id;
            $book->fill($data)->save();
        });
    }
}
