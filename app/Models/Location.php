<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'description', 'parent_id', 'map_image_path', 'pin_x', 'pin_y'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

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
