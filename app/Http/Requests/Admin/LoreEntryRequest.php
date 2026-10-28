<?php

namespace App\Http\Requests\Admin;

use App\Models\LoreEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoreEntryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(LoreEntry::CATEGORIES))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
