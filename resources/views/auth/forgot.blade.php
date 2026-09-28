{{-- DDE-Mart Admin — forgot password (original view, UI kit) --}}
@php($siteName = \App\Models\Setting::get('site_name', 'DDE-Mart'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — {{ $siteName }} Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-950 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="rounded-2xl bg-white p-6 shadow-2xl">
            <h1 class="text-lg font-black tracking-tight">Forgot password</h1>
            <p class="mb-5 text-sm text-slate-500">We email you a reset link valid 60 minutes.</p>

            @if (session('success'))
                <div class="alert-ok">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <x-field label="Email" for="email">
                    <x-input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" />
                </x-field>
                <x-btn class="w-full">Send reset link</x-btn>
            </form>

            <p class="mt-4 text-center text-sm"><a href="{{ route('login') }}" class="link">Back to sign in</a></p>
        </div>
    </div>
</body>
</html>
