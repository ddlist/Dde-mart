<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/*
 * DDE-Mart Admin — auto-slug trait (original).
 * Fills `slug` from `name`/`title` on create; refreshes on rename unless customized.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            $model->slug ??= static::makeSlug($model);
        });

        static::updating(function ($model) {
            if ($model->isDirty(static::slugSource()) && ! $model->isDirty('slug')) {
                $model->slug = static::makeSlug($model);
            }
        });
    }

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected static function makeSlug($model): string
    {
        $source = static::slugSource();

        return Str::slug((string) ($model->{$source} ?? '')) ?: Str::random(8);
    }
}
