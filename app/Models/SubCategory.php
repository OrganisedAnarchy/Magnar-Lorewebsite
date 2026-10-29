<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'name'])]
class SubCategory extends Model
{
    /**
     * Known sub categories grouped by category key.
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return self::query()
            ->orderBy('name')
            ->get()
            ->groupBy('category')
            ->map(fn ($items): array => $items->pluck('name')->all())
            ->all();
    }

    /**
     * Remember a sub category so it is suggested next time (case-insensitive).
     */
    public static function remember(?string $category, ?string $name): void
    {
        if (blank($category) || blank($name)) {
            return;
        }

        $exists = self::query()
            ->where('category', $category)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if (! $exists) {
            self::create(['category' => $category, 'name' => $name]);
        }
    }
}
