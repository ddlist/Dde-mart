<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminResetLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — staff password reset (original controller).
 * Link-based (signed URL, 60 minutes) instead of token tables: request
 * emails the link, the link carries the user + expiry in its signature.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Always report success: never leak which emails exist.
        if ($user) {
            $url = URL::temporarySignedRoute(
                'password.reset',
                now()->addHour(),
                ['user' => $user->id]
            );

            Mail::to($user->email)->send(new AdminResetLink($url));
        }

        return back()->with('success', 'If that email exists, a reset link is on its way.');
    }

    public function show(Request $request, User $user): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')
                ->with('error', 'That reset link is invalid or expired.');
        }

        return view('auth.reset', ['email' => $user->email, 'user' => $user]);
    }

    public function reset(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')
                ->with('error', 'That reset link is invalid or expired.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('login')
            ->with('success', 'Password updated. Sign in with the new one.');
    }
}
