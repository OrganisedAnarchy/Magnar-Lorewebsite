<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'organisation_rank_id',
    'join_day',
    'join_month',
    'join_cycle',
    'join_year',
    'join_is_circa',
    'is_current',
])]
class OrganisationMember extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_day' => 'integer',
            'join_month' => 'integer',
            'join_cycle' => 'integer',
            'join_year' => 'integer',
            'join_is_circa' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'organisation_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'member_id');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(OrganisationRank::class, 'organisation_rank_id');
    }

    /**
     * The join date formatted as day-month-cycle-year, or null when not recorded.
     */
    public function joinDateLabel(): ?string
    {
        if ($this->join_year === null) {
            return null;
        }

        $label = implode('-', [$this->join_day, $this->join_month, $this->join_cycle, $this->join_year]);

        return $this->join_is_circa ? "c. {$label}" : $label;
    }
}
