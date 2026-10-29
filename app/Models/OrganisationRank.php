<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'level'])]
class OrganisationRank extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['level' => 'integer'];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'organisation_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganisationMember::class);
    }
}
