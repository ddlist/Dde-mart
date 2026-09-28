{{-- DDE-Mart Admin — login page (original view, UI kit) --}}
@php($siteName = \App\Models\Setting::get('site_name', 'DDE-Mart'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ $siteName }} Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-950 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center justify-center gap-2.5 text-white">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-500 text-lg font-black text-slate-950">D</span>
            <div>
                <p class="text-base font-bold tracking-wide">{{ $siteName }}</p>
                <p class="text-xs text-slate-400">Admin Panel</p>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-2xl">
            <h1 class="text-lg font-black tracking-tight">Sign in</h1>
            <p class="mb-5 text-sm text-slate-500">Staff access only. No public registration.</p>

            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf
                <x-field label="Email" for="email">
                    <x-input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" />
                </x-field>
                <x-field label="Password" for="password">
                    <x-input id="password" name="password" type="password" required />
                </x-field>
                <x-check name="remember" value="1" label="Remember me" />
                <x-btn class="w-full">Sign in</x-btn>
            </form>
            <p class="mt-4 text-center text-sm"><a href="{{ route('password.request') }}" class="link">Forgot password?</a></p>
        </div>
        <p class="mt-4 text-center text-xs text-slate-500">DDE-Mart · clean rebuild, no legacy code</p>
    </div>
</body>
</html>
