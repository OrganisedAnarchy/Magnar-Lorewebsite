<?php

namespace App\Models;

use App\Support\LoreImages;

/**
 * An optional image (portrait, emblem, cover…) stored at `image_path`.
 */
trait HasLoreImage
{
    public static function imageFolder(): string
    {
        return 'lore/'.(new static)->getTable();
    }

    /**
     * What the image is called in the admin form, e.g. "Portrait" or "Emblem".
     */
    public function imageLabel(): string
    {
        return 'Image';
    }

    public function imageUrl(): ?string
    {
        return LoreImages::displayUrl($this->image_path);
    }
}
