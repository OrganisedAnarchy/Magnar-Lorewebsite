<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'description', 'summary', 'celestial_kind', 'parent_id', 'map_image_path', 'pin_x', 'pin_y'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    public const CELESTIAL_KINDS = ['Planet', 'Moon', 'Sun'];

    public function isCelestialBody(): bool
    {
        return filled($this->celestial_slug);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCelestialBodies(Builder $query): Builder
    {
        return $query->whereNotNull('celestial_slug');
    }

    /**
     * The celestial bodies keyed by slug, as shown on the interactive system chart.
     *
     * @return array<string, array{slug: string, name: string, kind: ?string, summary: ?string}>
     */
    public static function celestialChartData(): array
    {
        return self::query()->celestialBodies()->get()
            ->mapWithKeys(fn (self $body): array => [$body->celestial_slug => [
                'slug' => $body->celestial_slug,
                'name' => $body->name,
                'kind' => $body->celestial_kind,
                'summary' => $body->summary,
            ]])
            ->all();
    }

    /**
     * The lore split into paragraphs on blank lines.
     *
     * @return list<string>
     */
    public function loreParagraphs(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', (string) $this->description)),
            filled(...),
        ));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pin_x' => 'float',
            'pin_y' => 'float',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function hasMap(): bool
    {
        return filled($this->map_image_path);
    }

    public function mapUrl(): ?string
    {
        return $this->hasMap() ? Storage::disk('public')->url($this->map_image_path) : null;
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * The chain of ancestors from the top-level location down to this one's parent.
     *
     * @return list<self>
     */
    public function ancestors(): array
    {
        $chain = [];
        $current = $this->parent;

        while ($current !== null && count($chain) < 100) {
            array_unshift($chain, $current);
            $current = $current->parent;
        }

        return $chain;
    }
}
