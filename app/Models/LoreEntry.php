<?php

namespace App\Models;

use Database\Factories\LoreEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['category', 'title', 'description'])]
class LoreEntry extends Model
{
    /** @use HasFactory<LoreEntryFactory> */
    use HasFactory;

    /**
     * @var array<string, array{label: string, examples: string}>
     */
    public const CATEGORIES = [
        'magic' => ['label' => 'Magic & the Arcane', 'examples' => 'Spells, systems, phenomena'],
        'places' => ['label' => 'Places & Geography', 'examples' => 'Celestial bodies, countries, regions, cities, realms'],
        'organisations' => ['label' => 'Organisations & Factions', 'examples' => 'Guilds, houses, governments'],
        'nature' => ['label' => 'Nature & Life', 'examples' => 'Species, flora, fauna'],
        'people' => ['label' => 'People & Beings', 'examples' => 'Characters, gods, spirits'],
        'religion' => ['label' => 'Religion & Beliefs', 'examples' => 'Faiths, rituals, afterlife'],
        'technology' => ['label' => 'Technology & Craft', 'examples' => 'Inventions, engineering'],
        'items' => ['label' => 'Items & Artefacts', 'examples' => 'Relics, equipment, books'],
        'languages' => ['label' => 'Languages & Writing', 'examples' => 'Conlangs, scripts, names'],
        'history' => ['label' => 'History & Events', 'examples' => 'Eras, wars, timelines'],
        'culture' => ['label' => 'Culture & Society', 'examples' => 'Customs, law, trade'],
        'cosmology' => ['label' => 'Cosmology', 'examples' => 'Reality, dimensions, fate'],
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? $this->category;
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_lore_entry');
    }
}
