<?php

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'phonemes', 'phonology_notes', 'syllables', 'grammar', 'writing_system', 'image_path'])]
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    use HasLoreImage;

    public function imageLabel(): string
    {
        return 'Script sample';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phonemes' => 'array',
            'syllables' => 'array',
        ];
    }

    public function words(): HasMany
    {
        return $this->hasMany(LanguageWord::class)->orderBy('word');
    }
}
