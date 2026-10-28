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
        $rules = [
            'category' => ['required', Rule::in(array_keys(LoreEntry::CATEGORIES))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];

        foreach ([
            'dob' => ['day' => 'between:1,30', 'month' => 'between:1,13', 'cycle' => 'between:1,2', 'year' => 'min:0'],
            'dod' => ['day' => 'between:1,30', 'month' => 'between:1,13', 'cycle' => 'between:1,2', 'year' => 'min:0'],
        ] as $date => $components) {
            $fields = array_map(fn (string $component): string => "{$date}_{$component}", array_keys($components));

            foreach ($components as $component => $range) {
                $field = "{$date}_{$component}";
                $otherFields = implode(',', array_diff($fields, [$field]));

                $rules[$field] = [
                    'exclude_unless:category,people',
                    'nullable',
                    'required_with:'.$otherFields,
                    'integer',
                    $range,
                ];
            }
        }

        return $rules;
    }
}
