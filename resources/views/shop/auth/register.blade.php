{{-- DDE-Mart storefront — register (original view) --}}
<x-store-layout title="Create account">
    <div class="mx-auto w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-lg font-black tracking-tight">Create account</h1>
        <p class="mb-5 text-sm text-slate-500">Order in a minute. No card required.</p>

        <form method="POST" action="{{ route('shop.register.store') }}" class="space-y-4">
            @csrf
            <x-field label="Name" for="name" :error="$errors->first('name')">
                <x-input id="name" name="name" required value="{{ old('name') }}" />
            </x-field>
            <x-field label="Phone" for="phone" :error="$errors->first('phone')">
                <x-input id="phone" name="phone" required value="{{ old('phone') }}" />
            </x-field>
            <x-field label="Email (optional)" for="email" :error="$errors->first('email')">
                <x-input id="email" name="email" type="email" value="{{ old('email') }}" />
            </x-field>
            <x-field label="Password (optional — OTP works too)" for="password" :error="$errors->first('password')">
                <x-input id="password" name="password" type="password" />
            </x-field>
            <x-field label="Confirm password" for="password_confirmation">
                <x-input id="password_confirmation" name="password_confirmation" type="password" />
            </x-field>
            <x-btn class="w-full">Create account</x-btn>
        </form>

        <p class="mt-4 text-center text-sm text-slate-500">
            Have an account? <a href="{{ route('shop.login') }}" class="font-bold text-emerald-700">Sign in</a>
        </p>
    </div>
</x-store-layout>
