<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — key/value ops settings (original).
 * Non-secret knobs only — gateway secrets live in .env, never here.
 * Reads fall back to config/settings.php defaults so fresh installs work pre-seed.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = static::where('key', $key)->value('value');

        if ($stored !== null) {
            return $stored;
        }

        return config("settings.defaults.{$key}", $default);
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
    }

    /** Boolean read for toggle keys ('1' vs anything else). */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /** All known settings merged: stored values over config defaults. */
    public static function allMerged(): array
    {
        $defaults = config('settings.defaults', []);
        $stored = static::pluck('value', 'key')->all();

        return array_merge($defaults, $stored);
    }
}
