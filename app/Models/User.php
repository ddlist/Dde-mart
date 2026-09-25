<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
     * DDE-Mart RBAC (original). A user has exactly one role (mirrors legacy
     * `users.role_id` semantics, now a real FK). Users without a role can
     * authenticate but are denied every protected area.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role?->is_super;
    }

    /**
     * Check a "<group>[.<ability>]" grant, e.g. can('roles') or can('roles','edit').
     * Super-admins pass everything.
     */
    public function canAccess(string $group, ?string $ability = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->relationLoaded('role') ? $this->getRelation('role') : $this->role()->first();

        if (! $role) {
            return false;
        }

        return $role->permissions()
            ->where('group', $group)
            ->when($ability, fn ($q) => $q->where('ability', $ability))
            ->exists();
    }
}
