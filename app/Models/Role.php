<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/*
 * DDE-Mart Admin — Role model (original implementation).
 * Proper Model base, plural table, slug + is_super flag, real relations.
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_super'];

    protected function casts(): array
    {
        return ['is_super' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            $role->slug ??= Str::slug($role->name);
        });

        static::updating(function (Role $role) {
            if ($role->isDirty('name') && ! $role->isDirty('slug')) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** All granted "<group>.<ability>" keys for this role. */
    public function abilityKeys(): array
    {
        return $this->permissions()
            ->get(['group', 'ability'])
            ->map(fn (Permission $p) => "{$p->group}.{$p->ability}")
            ->all();
    }

    /** Replace this role's abilities with the given "<group>.<ability>" keys. */
    public function syncAbilities(array $keys): void
    {
        $valid = collect(config('admin_permissions'))
            ->flatMap(fn (array $group, string $name) => array_map(
                fn (string $ability) => "{$name}.{$ability}",
                array_keys($group['abilities']),
            ))
            ->flip();

        $rows = collect($keys)
            ->filter(fn (string $key) => isset($valid[$key]))
            ->map(function (string $key) {
                [$group, $ability] = explode('.', $key, 2);

                return new Permission(['group' => $group, 'ability' => $ability]);
            })
            ->all();

        $this->permissions()->delete();
        $this->permissions()->saveMany($rows);
    }
}
