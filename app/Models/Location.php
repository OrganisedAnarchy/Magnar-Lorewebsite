<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'description', 'is_celestial_body', 'summary', 'celestial_kind', 'location_type', 'building_type', 'settlement_type', 'parent_id', 'map_image_path', 'pin_x', 'pin_y'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    public const CELESTIAL_KINDS = ['Planet', 'Moon', 'Sun'];

    public const TYPE_BUILDING = 'Building';

    public const TYPE_RELIGIOUS_SETTLEMENT = 'Religious Settlement';

    public const LOCATION_TYPES = ['Continent', 'Country', 'City', 'Town', 'Village', self::TYPE_BUILDING, self::TYPE_RELIGIOUS_SETTLEMENT];

    public function isCelestialBody(): bool
    {
        return (bool) $this->is_celestial_body;
    }

    /**
     * Whether this is one of the bodies on the system chart, which cannot be deleted or stop being celestial.
     */
    public function isChartBody(): bool
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
            'is_celestial_body' => 'boolean',
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

    public static function imageDisk(): Filesystem
    {
        return Storage::disk(config('filesystems.images_disk'));
    }

    public function mapUrl(): ?string
    {
        if (! $this->hasMap()) {
            return null;
        }

        $disk = static::imageDisk();

        // S3 buckets are private by default, so serve maps through short-lived signed URLs.
        return config('filesystems.disks.'.config('filesystems.images_disk').'.driver') === 's3'
            ? $disk->temporaryUrl($this->map_image_path, now()->addHour())
            : $disk->url($this->map_image_path);
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
