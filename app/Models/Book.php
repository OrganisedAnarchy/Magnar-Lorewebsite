<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'author_id',
    'languages',
    'summary',
    'content',
    'published_day',
    'published_month',
    'published_cycle',
    'published_year',
    'published_is_circa',
    'published_is_unknown',
    'image_path',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    use HasLoreImage;

    public const DEFAULT_LANGUAGE = 'Common';

    public function imageLabel(): string
    {
        return 'Cover';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'published_is_circa' => 'boolean',
            'published_is_unknown' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(LoreEntry::class, 'author_id');
    }

    /**
     * Find the person with this name, or create them as a placeholder for the book's author.
     */
    public static function resolveAuthor(string $name, string $bookTitle): LoreEntry
    {
        $name = trim($name);

        return LoreEntry::query()
            ->where('category', 'people')
            ->whereRaw('lower(title) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->first()
            ?? LoreEntry::create([
                'category' => 'people',
                'title' => $name,
                'description' => "author of [[{$bookTitle}]]",
            ]);
    }
}
