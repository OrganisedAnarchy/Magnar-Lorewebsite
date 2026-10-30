<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(): View
    {
        return view('admin.languages.index', [
            'languages' => Language::query()->withCount('words')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.languages.form', ['language' => new Language]);
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        $this->persist(new Language, $request->validated());

        return redirect()->route('admin.languages.index')->with('status', 'Language created.');
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.form', ['language' => $language->load('words')]);
    }

    public function update(LanguageRequest $request, Language $language): RedirectResponse
    {
        $this->persist($language, $request->validated());

        return redirect()->route('admin.languages.index')->with('status', 'Language updated.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('admin.languages.index')->with('status', 'Language deleted.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Language $language, array $data): void
    {
        $words = $data['words'] ?? [];
        unset($data['words']);

        $data['phonemes'] = array_values(array_unique($data['phonemes'] ?? []));
        $data['syllables'] = array_values(array_unique($data['syllables'] ?? []));

        DB::transaction(function () use ($language, $data, $words): void {
            $language->fill($data)->save();
            $language->words()->delete();
            $language->words()->createMany(array_map(fn (array $word): array => [
                'word' => $word['word'],
                'meaning' => $word['meaning'] ?? null,
                'notes' => $word['notes'] ?? null,
            ], array_values($words)));
        });
    }
}
