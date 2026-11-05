<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['relative_id', 'relationship'])]
class FamilyMember extends Model
{
    /**
     * Each relationship mapped to the label shown and its inverse.
     *
     * @var array<string, array{label: string, inverse: string}>
     */
    public const RELATIONSHIPS = [
        'parent' => ['label' => 'Parent', 'inverse' => 'child'],
        'child' => ['label' => 'Child', 'inverse' => 'parent'],
        'spouse' => ['label' => 'Spouse', 'inverse' => 'spouse'],
        'sibling' => ['label' => 'Sibling', 'inverse' => 'sibling'],
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'person_id');
    }

    public function relative(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'relative_id');
    }

    public function relationshipLabel(): string
    {
        return self::RELATIONSHIPS[$this->relationship]['label'] ?? ucfirst($this->relationship);
    }
}
