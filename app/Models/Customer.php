<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/*
 * DDE-Mart API — Customer model (original). App accounts, NOT staff.
 * Password nullable: OTP-first onboarding (imported users have no password).
 */
class Customer extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'legacy_id', 'name', 'phone', 'email', 'password', 'avatar_path', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function pushTokens(): MorphMany
    {
        return $this->morphMany(PushToken::class, 'tokenable');
    }
}
