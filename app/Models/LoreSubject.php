<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['category', 'name', 'overview', 'place_id'])]
class LoreSubject extends Model
{
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_lore_subject');
    }
}
