{{-- DDE-Mart — public vendor onboarding (original view, UI kit) --}}
@php($siteName = \App\Models\Setting::get('site_name', 'DDE-Mart'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sell on {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-950 px-4 py-12">
    <div class="w-full max-w-lg">
        <div class="rounded-2xl bg-white p-6 shadow-2xl">
            <h1 class="text-lg font-black tracking-tight">Sell on {{ $siteName }}</h1>
            <p class="mb-5 text-sm text-slate-500">Apply in a minute. Staff reviews every application before activation.</p>

            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('onboarding.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-field label="Your name" for="name">
                    <x-input id="name" name="name" required value="{{ old('name') }}" />
                </x-field>
                <x-field label="Phone" for="phone">
                    <x-input id="phone" name="phone" required value="{{ old('phone') }}" />
                </x-field>
                <x-field label="Email (optional)" for="email">
                    <x-input id="email" name="email" type="email" value="{{ old('email') }}" />
                </x-field>
                <x-field label="Store name" for="store_name">
                    <x-input id="store_name" name="store_name" required value="{{ old('store_name') }}" />
                </x-field>
                <x-field label="Store phone (optional)" for="store_phone">
                    <x-input id="store_phone" name="store_phone" value="{{ old('store_phone') }}" />
                </x-field>
                <x-field label="Address (optional)" for="address">
                    <x-input id="address" name="address" value="{{ old('address') }}" />
                </x-field>
                <x-field label="Section (optional)" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">—</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Logo (optional)" for="logo">
                    <x-input id="logo" name="logo" type="file" />
                </x-field>
                <x-btn class="w-full">Submit application</x-btn>
            </form>
        </div>
    </div>
</body>
</html>
