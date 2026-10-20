<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'description', 'world_year', 'cycle', 'month', 'day', 'place_id'])]
class Event extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'world_year' => 'integer',
            'cycle' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function loreSubjects(): BelongsToMany
    {
        return $this->belongsToMany(LoreSubject::class, 'event_lore_subject');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'event_tag');
    }
}
