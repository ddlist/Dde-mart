{{-- DDE-Mart Admin — reset password (original view, UI kit) --}}
@php($siteName = \App\Models\Setting::get('site_name', 'DDE-Mart'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password — {{ $siteName }} Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-950 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="rounded-2xl bg-white p-6 shadow-2xl">
            <h1 class="text-lg font-black tracking-tight">Reset password</h1>
            <p class="mb-5 text-sm text-slate-500">For {{ $email }}. Minimum 8 characters.</p>

            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ URL::current() }}" class="space-y-4">
                @csrf
                <x-field label="New password" for="password">
                    <x-input id="password" name="password" type="password" required autofocus />
                </x-field>
                <x-field label="Confirm password" for="password_confirmation">
                    <x-input id="password_confirmation" name="password_confirmation" type="password" required />
                </x-field>
                <x-btn class="w-full">Update password</x-btn>
            </form>
        </div>
    </div>
</body>
</html>
