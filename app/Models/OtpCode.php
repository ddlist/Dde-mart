<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
 * DDE-Mart API — OTP code (original). 6-digit, hashed at rest, 10-min TTL.
 * Delivery is log-driver locally; wire an SMS gateway to OtpService::deliver().
 */
class OtpCode extends Model
{
    use HasFactory;

    public const TTL_MINUTES = 10;

    public const MAX_PER_HOUR = 5;

    public const MAX_ATTEMPTS = 5;

    protected $fillable = ['phone', 'code', 'attempts', 'expires_at', 'consumed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    /** Issue a code; returns [model, plain code]. Plain code is never stored. */
    public static function issue(string $phone): array
    {
        static::where('phone', $phone)->whereNull('consumed_at')->delete();

        $plain = (string) random_int(100000, 999999);

        $code = static::create([
            'phone' => $phone,
            'code' => Hash::make($plain),
            'expires_at' => now()->addMinutes(static::TTL_MINUTES),
        ]);

        return [$code, $plain];
    }

    public function check(string $plain): bool
    {
        if ($this->consumed_at || $this->expires_at->isPast() || $this->attempts >= static::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($plain, $this->code)) {
            $this->increment('attempts');

            return false;
        }

        $this->update(['consumed_at' => now()]);

        return true;
    }

    public static function recentCount(string $phone): int
    {
        return static::where('phone', $phone)->where('created_at', '>=', now()->subHour())->count();
    }
}
