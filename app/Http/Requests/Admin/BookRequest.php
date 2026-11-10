<?php

namespace App\Http\Requests\Admin;

use App\Support\LoreImages;
use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    /**
     * An unknown date has no parts and cannot be circa.
     */
    protected function prepareForValidation(): void
    {
        $isUnknown = $this->boolean('published_is_unknown');
        $normalized = [
            'published_is_unknown' => $isUnknown,
            'published_is_circa' => ! $isUnknown && $this->boolean('published_is_circa'),
            'remove_image' => $this->boolean('remove_image'),
        ];

        if ($isUnknown) {
            foreach (['day', 'month', 'cycle', 'year'] as $component) {
                $normalized["published_{$component}"] = null;
            }
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $components = ['day' => 'between:1,30', 'month' => 'between:1,13', 'cycle' => 'between:1,2', 'year' => 'min:0'];
        $fields = array_map(fn (string $component): string => "published_{$component}", array_keys($components));

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'array', 'max:50'],
            'languages.*' => ['string', 'max:100'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'published_is_circa' => ['boolean'],
            'published_is_unknown' => ['boolean'],
            'image' => ['nullable', ...LoreImages::FILE_RULES],
            'remove_image' => ['boolean'],
        ];

        foreach ($components as $component => $range) {
            $field = "published_{$component}";

            $rules[$field] = [
                'nullable',
                'required_with:'.implode(',', array_diff($fields, [$field])),
                'required_if:published_is_circa,1',
                'integer',
                $range,
            ];
        }

        return $rules;
    }
}
