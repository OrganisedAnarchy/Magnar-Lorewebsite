<?php

namespace App\Models;

use Database\Factories\LanguageWordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word', 'meaning', 'notes'])]
class LanguageWord extends Model
{
    /** @use HasFactory<LanguageWordFactory> */
    use HasFactory;

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
