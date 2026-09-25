<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * DDE-Mart Admin — image upload helper (original).
 * Stores on the `public` disk; callers keep the returned path in `*_path` columns.
 * Legacy left orphaned files in Storage; rebuild deletes the old file on replace.
 */
class ImageUploads
{
    public static function store(?UploadedFile $file, string $directory = 'catalog'): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    public static function replace(?UploadedFile $file, ?string $oldPath, string $directory = 'catalog'): ?string
    {
        $newPath = static::store($file, $directory);

        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath ?? $oldPath;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
