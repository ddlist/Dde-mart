{{-- DDE-Mart Admin — 403 page (original view) --}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forbidden — DDE-Mart Admin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-full place-items-center bg-slate-100 px-4">
    <div class="max-w-sm text-center">
        <p class="text-6xl font-black text-slate-300">403</p>
        <h1 class="mt-2 text-lg font-bold">You don't have access here</h1>
        <p class="mt-1 text-sm text-slate-500">Your role doesn't include this area. Ask an administrator to grant it.</p>
        <a href="{{ route('admin.dashboard') }}"
           class="mt-5 inline-block rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white">Back to dashboard</a>
    </div>
</body>
</html>
