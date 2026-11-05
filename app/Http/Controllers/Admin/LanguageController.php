<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LanguageRequest;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(LanguageRequest $request): JsonResponse|RedirectResponse
    {
        $language = new Language;
        $this->persist($language, $request->validated());

        return $this->respond($request, redirect()->route('admin.languages.index'), 'Language created.', $this->savedPayload($language));
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.form', ['language' => $language->load('words')]);
    }

    public function update(LanguageRequest $request, Language $language): JsonResponse|RedirectResponse
    {
        $this->persist($language, $request->validated());

        return $this->respond($request, redirect()->route('admin.languages.index'), 'Language updated.', $this->savedPayload($language));
    }

    public function destroy(Request $request, Language $language): JsonResponse|RedirectResponse
    {
        $language->delete();

        return $this->respond($request, redirect()->route('admin.languages.index'), 'Language deleted.');
    }

    /**
     * @return array{id: int, action: string, method: string, edit_url: string}
     */
    private function savedPayload(Language $language): array
    {
        return [
            'id' => $language->id,
            'action' => route('admin.languages.update', $language),
            'method' => 'PUT',
            'edit_url' => route('admin.languages.edit', $language),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Language $language, array $data): void
    {
        $words = $data['words'] ?? [];
        unset($data['words']);

        $data['phonemes'] = array_values(array_unique($data['phonemes'] ?? $language->phonemes ?? []));
        $data['syllables'] = collect($data['syllables'] ?? [])
            ->map(fn (array $syllable): array => [
                'ipa' => trim((string) ($syllable['ipa'] ?? '')),
                'romanized' => trim($syllable['romanized']),
            ])
            ->unique(fn (array $syllable): string => $syllable['ipa'].'|'.$syllable['romanized'])
            ->values()
            ->all();

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
