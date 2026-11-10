<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores lore imagery on the configured images disk (S3 in production).
 */
class LoreImages
{
    /**
     * Validation rules for an uploaded image.
     *
     * @var list<string>
     */
    public const FILE_RULES = ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'];

    public static function diskName(): string
    {
        return config('filesystems.images_disk');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    private static function isS3(): bool
    {
        return config('filesystems.disks.'.self::diskName().'.driver') === 's3';
    }

    /**
     * Upload an image and return its stored path.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        return (string) $file->store($folder, self::diskName());
    }

    public static function delete(?string $path): void
    {
        if (filled($path)) {
            self::disk()->delete($path);
        }
    }

    /**
     * The permanent URL of a stored image, suitable for saving inside Markdown.
     */
    public static function publicUrl(string $path): string
    {
        return self::disk()->url($path);
    }

    /**
     * A URL for showing a stored image in the admin; S3 buckets are private by default, so these are signed.
     */
    public static function displayUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return self::isS3()
            ? self::disk()->temporaryUrl($path, now()->addHour())
            : self::disk()->url($path);
    }

    /**
     * Apply an uploaded or removed `image` field from a form request to a record.
     *
     * Returns the `image_path` attribute to save, or an empty array when the image is unchanged.
     *
     * @return array{image_path?: ?string}
     */
    public static function fromRequest(Request $request, ?string $currentPath, string $folder): array
    {
        if ($request->hasFile('image')) {
            self::delete($currentPath);

            return ['image_path' => self::store($request->file('image'), $folder)];
        }

        if ($request->boolean('remove_image')) {
            self::delete($currentPath);

            return ['image_path' => null];
        }

        return [];
    }
}
