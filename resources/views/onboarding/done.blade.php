{{-- DDE-Mart — onboarding received (original view, UI kit) --}}
@php($siteName = \App\Models\Setting::get('site_name', 'DDE-Mart'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application received — {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-950 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="rounded-2xl bg-white p-6 text-center shadow-2xl">
            <h1 class="text-lg font-black tracking-tight">Application received</h1>
            <p class="mt-2 text-sm text-slate-500">Staff reviews every application. We contact you on your phone once approved.</p>
            <p class="mt-4"><a href="/" class="link">Back to home</a></p>
        </div>
    </div>
</body>
</html>
