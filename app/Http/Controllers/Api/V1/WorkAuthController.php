<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\OtpCode;
use App\Models\Owner;
use App\Models\Provider;
use App\Models\ProviderWorker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
 * DDE-Mart API — workforce auth (original). OTP-only login for driver, vendor
 * (owner directory), owner, provider, worker (provider staff) accounts,
 * resolved by phone per role. Tokens carry the singular role ability.
 */
class WorkAuthController extends Controller
{
    public const ROLES = ['driver', 'vendor', 'owner', 'provider', 'worker'];

    public function otpRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:'.implode(',', self::ROLES)],
        ]);

        $key = 'work-otp:'.$request->ip().':'.$validated['role'].':'.$validated['phone'];

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many attempts. Try later.'], 429);
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

        if (OtpCode::recentCount($validated['role'].':'.$validated['phone']) >= OtpCode::MAX_PER_HOUR) {
            return response()->json(['message' => 'Too many codes requested. Try later.'], 429);
        }

        [$code, $plain] = OtpCode::issue($validated['role'].':'.$validated['phone']);
        Log::info('Workforce OTP issued', ['role' => $validated['role'], 'code' => $plain]);

        return response()->json(['data' => [
            'expires_in' => OtpCode::TTL_MINUTES * 60,
            'debug_code' => app()->isProduction() ? null : $plain,
        ]]);
    }

    public function otpVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:'.implode(',', self::ROLES)],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $phoneKey = $validated['role'].':'.$validated['phone'];

        $code = OtpCode::where('phone', $phoneKey)->whereNull('consumed_at')->latest()->first();

        if (! $code || ! $code->check($validated['code'])) {
            return response()->json(['message' => 'Invalid or expired code.'], 401);
        }

        $record = $this->resolve($validated['role'], $validated['phone']);

        if (! $record) {
            return response()->json(['message' => 'No account for this number.'], 404);
        }

        if (($record->status ?? 'active') !== 'active' || ($record->is_active ?? true) === false) {
            return response()->json(['message' => 'Account is '.($record->status ?? 'inactive').'.'], 403);
        }

        $token = $record->createToken('work-app', [$validated['role']])->plainTextToken;

        return response()->json(['data' => [
            'id' => $record->id,
            'name' => $record->name,
            'role' => $validated['role'],
            'token' => $token,
            'token_type' => 'Bearer',
        ]]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone ?? null,
            'kind' => $user->kind ?? null,
            'status' => $user->status ?? null,
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    protected function resolve(string $role, string $phone)
    {
        return match ($role) {
            'driver' => Driver::where('phone', $phone)->first(),
            'vendor', 'owner' => Owner::where('phone', $phone)->first(),
            'provider' => Provider::where('phone', $phone)->first(),
            'worker' => ProviderWorker::where('phone', $phone)->first(),
        };
    }
}
