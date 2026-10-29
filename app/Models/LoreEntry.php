<?php

namespace App\Models;

use Database\Factories\LoreEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'category',
    'title',
    'description',
    'dob_day',
    'dob_month',
    'dob_cycle',
    'dob_year',
    'dob_is_circa',
    'dob_is_unknown',
    'dod_day',
    'dod_month',
    'dod_cycle',
    'dod_year',
    'dod_is_circa',
    'dod_is_unknown',
])]
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

    /**
     * Split the text of a wikilink into title, alias and category.
     *
     * @return array{title: string, alias: ?string, category: string}
     */
    public static function parseWikilink(string $inner): array
    {
        $at = strrpos($inner, '@');
        $left = $at === false ? $inner : substr($inner, 0, $at);
        $category = $at === false ? '' : trim(substr($inner, $at + 1));
        $pipe = strpos($left, '|');
        $alias = $pipe === false ? null : trim(substr($left, $pipe + 1));

        return [
            'title' => trim($pipe === false ? $left : substr($left, 0, $pipe)),
            'alias' => $alias === '' ? null : $alias,
            'category' => $category,
        ];
    }

    /**
     * Whether each wikilink in the given text points at an existing entry.
     *
     * @return array<int, array{title: string, category: string, exists: bool}>
     */
    public static function linkStatuses(?string $text): array
    {
        preg_match_all('/\[\[([^\]\n]+)\]\]/', (string) $text, $matches);

        return collect($matches[1])
            ->map(fn (string $inner): array => self::parseWikilink($inner))
            ->filter(fn (array $link): bool => $link['title'] !== '')
            ->unique(fn (array $link): string => mb_strtolower($link['title'].'@'.$link['category']))
            ->map(fn (array $link): array => [
                'title' => $link['title'],
                'category' => $link['category'],
                'exists' => self::linkExists($link['title'], $link['category']),
            ])
            ->values()
            ->all();
    }

    public static function linkExists(string $title, ?string $category): bool
    {
        $resolved = filled($category) ? self::resolveCategory($category) : null;

        if (filled($category) && $resolved === null) {
            return false;
        }

        return self::query()
            ->whereRaw('lower(title) = ?', [mb_strtolower(trim($title))])
            ->when($resolved, fn ($query) => $query->where('category', $resolved))
            ->exists();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? 'Uncategorized';
    }

    /**
     * Match a category key or label (case-insensitive) to its key.
     */
    public static function resolveCategory(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        foreach (self::CATEGORIES as $key => $info) {
            if ($value === $key || $value === mb_strtolower($info['label'])) {
                return $key;
            }
        }

        return null;
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_lore_entry');
    }
}
