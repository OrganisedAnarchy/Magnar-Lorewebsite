<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class PlaceType extends Model
{
    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'place_place_type');
    }
}
