<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['remove_map' => $this->boolean('remove_map')]);

        if (blank($this->input('parent_id'))) {
            $this->merge(['parent_id' => null, 'pin_x' => null, 'pin_y' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'pin_x' => ['nullable', 'required_with:parent_id', 'numeric', 'between:0,100'],
            'pin_y' => ['nullable', 'required_with:parent_id', 'numeric', 'between:0,100'],
            'map' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_map' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $location = $this->route('location');
            $parentId = $this->input('parent_id');

            if ($parentId !== null && ! $validator->errors()->has('parent_id')) {
                $parent = Location::find($parentId);

                if (! $parent->hasMap()) {
                    $validator->errors()->add('parent_id', 'Only a location with a map can contain other locations.');
                } elseif ($location instanceof Location && ($parent->is($location) || in_array($parent->id, $location->descendantIds(), true))) {
                    $validator->errors()->add('parent_id', 'A location cannot be placed inside itself or one of its own children.');
                }
            }

            if ($location instanceof Location) {
                $willHaveMap = $this->hasFile('map') || ($location->hasMap() && ! $this->boolean('remove_map'));

                if (! $willHaveMap && $location->children()->exists()) {
                    $validator->errors()->add('map', 'This location has child locations, so it must keep a map.');
                }
            }
        }];
    }
}
