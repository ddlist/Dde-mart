<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/*
 * DDE-Mart API — customer auth (original). OTP-first; passwords optional.
 * Tokens carry the `customer` ability. Throttled per IP + per phone.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:50', 'unique:customers,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'data' => $this->customerPayload($customer, $request),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('phone', $validated['phone'])->first();

        if (! $customer || ! $customer->password || ! Hash::check($validated['password'], $customer->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $customer->is_active) {
            return response()->json(['message' => 'Account disabled.'], 403);
        }

        return response()->json(['data' => $this->customerPayload($customer, $request)]);
    }

    public function otpRequest(Request $request): JsonResponse
    {
        $validated = $request->validate(['phone' => ['required', 'string', 'max:50']]);
        $key = 'otp:'.$request->ip().':'.$validated['phone'];

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many attempts. Try later.'], 429);
        }

        RateLimiter::hit($key, 3600);

        if (OtpCode::recentCount($validated['phone']) >= OtpCode::MAX_PER_HOUR) {
            return response()->json(['message' => 'Too many codes requested. Try later.'], 429);
        }

        [$code, $plain] = OtpCode::issue($validated['phone']);

        // Local delivery: log driver. Production wires an SMS gateway here.
        Log::info('OTP issued', ['phone' => $validated['phone'], 'code' => $plain]);

        return response()->json([
            'data' => [
                'expires_in' => OtpCode::TTL_MINUTES * 60,
                'debug_code' => app()->isProduction() ? null : $plain,
            ],
        ]);
    }

    public function otpVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:10'],
            'name' => ['nullable', 'string', 'max:200'],
        ]);

        $code = OtpCode::where('phone', $validated['phone'])
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $code || ! $code->check($validated['code'])) {
            return response()->json(['message' => 'Invalid or expired code.'], 401);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['phone']],
            ['name' => $validated['name'] ?? 'Customer'],
        );

        if (! $customer->is_active) {
            return response()->json(['message' => 'Account disabled.'], 403);
        }

        return response()->json(['data' => $this->customerPayload($customer, $request)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'phone' => $request->user()->phone,
            'email' => $request->user()->email,
            'avatar' => \App\Support\Images::url($request->user()->avatar_path),
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    /**
     * Delete my account (store-compliance). Tokens revoked, push tokens
     * dropped, identity anonymized; order records keep their snapshots.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = $request->user();

        if ($customer->password && ! Hash::check($validated['password'] ?? '', $customer->password)) {
            return response()->json(['message' => 'Password confirmation required.'], 422);
        }

        $customer->tokens()->delete();
        $customer->pushTokens()->delete();
        $customer->update([
            'name' => 'Deleted user',
            'phone' => 'deleted:'.$customer->id.':'.now()->timestamp,
            'email' => null,
            'password' => Str::random(40),
            'avatar_path' => null,
            'is_active' => false,
        ]);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Step 1 of password reset: code to an existing account's phone. */
    public function passwordRequest(Request $request): JsonResponse
    {
        $validated = $request->validate(['phone' => ['required', 'string', 'max:50']]);

        $customer = Customer::where('phone', $validated['phone'])->first();
        abort_unless($customer && $customer->is_active, 404, 'No active account for this number.');

        if (OtpCode::recentCount($validated['phone']) >= OtpCode::MAX_PER_HOUR) {
            return response()->json(['message' => 'Too many codes requested. Try later.'], 429);
        }

        [, $plain] = OtpCode::issue($validated['phone']);
        Log::info('Password-reset OTP issued', ['phone' => $validated['phone'], 'code' => $plain]);

        return response()->json(['data' => [
            'expires_in' => OtpCode::TTL_MINUTES * 60,
            'debug_code' => app()->isProduction() ? null : $plain,
        ]]);
    }

    /** Step 2: verified code sets a new password and signs the account in. */
    public function passwordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $customer = Customer::where('phone', $validated['phone'])->first();
        abort_unless($customer && $customer->is_active, 404, 'No active account for this number.');

        // Any valid unconsumed code works (newest first) — codes issued in
        // the same second share a timestamp, so latest() alone can misfire.
        $verified = OtpCode::where('phone', $validated['phone'])
            ->whereNull('consumed_at')
            ->latest()
            ->get()
            ->first(fn ($code) => $code->check($validated['code']));

        if (! $verified) {
            return response()->json(['message' => 'Invalid or expired code.'], 401);
        }

        $customer->update(['password' => $validated['password']]);

        return response()->json(['data' => $this->customerPayload($customer, $request)]);
    }

    protected function customerPayload(Customer $customer, Request $request): array
    {
        $token = $customer->createToken(
            'app:'.($request->header('X-App', 'customer')),
            ['customer']
        )->plainTextToken;

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
