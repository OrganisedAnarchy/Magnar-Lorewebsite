<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Book;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(BookRequest $request): JsonResponse|RedirectResponse
    {
        $book = new Book;
        $this->persist($book, $request->validated());

        return $this->respond($request, redirect()->route('admin.books.index'), 'Book created.', $this->savedPayload($book));
    }

    public function edit(Book $book): View
    {
        return view('admin.books.form', [
            'book' => $book->load('author'),
            'languageOptions' => $this->languageOptions(),
        ]);
    }

    public function update(BookRequest $request, Book $book): JsonResponse|RedirectResponse
    {
        $this->persist($book, $request->validated());

        return $this->respond($request, redirect()->route('admin.books.index'), 'Book updated.', $this->savedPayload($book));
    }

    public function destroy(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $book->delete();

        return $this->respond($request, redirect()->route('admin.books.index'), 'Book deleted.');
    }

    /**
     * @return array{id: int, action: string, method: string, edit_url: string}
     */
    private function savedPayload(Book $book): array
    {
        return [
            'id' => $book->id,
            'action' => route('admin.books.update', $book),
            'method' => 'PUT',
            'edit_url' => route('admin.books.edit', $book),
        ];
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
