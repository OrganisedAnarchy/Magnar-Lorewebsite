<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('languages', 'name')->ignore($this->route('language'))],
            'description' => ['nullable', 'string'],
            'phonemes' => ['nullable', 'array', 'max:300'],
            'phonemes.*' => ['string', 'max:20'],
            'phonology_notes' => ['nullable', 'string'],
            'syllables' => ['nullable', 'array', 'max:2000'],
            'syllables.*' => ['string', 'max:20'],
            'grammar' => ['nullable', 'string'],
            'writing_system' => ['nullable', 'string'],
            'words' => ['nullable', 'array', 'max:5000'],
            'words.*.word' => ['required', 'string', 'max:255'],
            'words.*.meaning' => ['nullable', 'string', 'max:255'],
            'words.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
