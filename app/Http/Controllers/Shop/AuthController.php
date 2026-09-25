<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — shopper auth (original). Session guard `customer`,
 * OTP-first with optional password. Staff guard untouched.
 */
class AuthController extends Controller
{
    public function login(): View
    {
        return view('shop.auth.login');
    }

    public function register(): View
    {
        return view('shop.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:50', 'unique:customers,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::create($validated);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('shop.home'))->with('success', "Welcome, {$customer->name}.");
    }

    public function attempt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('phone', $validated['phone'])->first();

        if (! $customer || ! $customer->password || ! Hash::check($validated['password'], $customer->password)) {
            return redirect()->route('shop.login')->with('error', 'Invalid credentials.')->withInput();
        }

        if (! $customer->is_active) {
            return redirect()->route('shop.login')->with('error', 'Account disabled.');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('shop.home'));
    }

    public function otp(Request $request): RedirectResponse
    {
        $validated = $request->validate(['phone' => ['required', 'string', 'max:50']]);

        if (OtpCode::recentCount($validated['phone']) >= OtpCode::MAX_PER_HOUR) {
            return redirect()->route('shop.login')->with('error', 'Too many codes. Try later.');
        }

        [$code, $plain] = OtpCode::issue($validated['phone']);
        Log::info('Shop OTP issued', ['phone' => $validated['phone'], 'code' => $plain]);

        return redirect()->route('shop.login')->with('success', 'Code sent. Enter it below to sign in.');
    }

    public function otpVerify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $code = OtpCode::where('phone', $validated['phone'])->whereNull('consumed_at')->latest()->first();

        if (! $code || ! $code->check($validated['code'])) {
            return redirect()->route('shop.login')->with('error', 'Invalid or expired code.');
        }

        $customer = Customer::firstOrCreate(['phone' => $validated['phone']], ['name' => 'Customer']);

        if (! $customer->is_active) {
            return redirect()->route('shop.login')->with('error', 'Account disabled.');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('shop.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home');
    }

    public function profile(): View
    {
        return view('shop.auth.profile', ['customer' => Auth::guard('customer')->user()]);
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email,'.$customer->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $customer->update($validated);

        return redirect()->route('shop.profile')->with('success', 'Profile saved.');
    }
}
