<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * DDE-Mart Admin — image URL resolver (original helper).
 * Fresh uploads live on the `public` disk; D10-imported rows keep their legacy
 * remote URLs until re-uploaded. Views must use this instead of Storage::url().
 */
class Images
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::url($path);
    }
}
