{{-- DDE-Mart storefront — forgot password (original view) --}}
<x-store-layout title="Forgot password">
    <div class="mx-auto w-full max-w-sm">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-lg font-black tracking-tight">Forgot password</h1>
            <p class="mb-5 text-sm text-slate-500">We will send a reset code to your phone.</p>

            @if (session('error'))
                <div class="alert-err">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('shop.forgot.send') }}" class="space-y-4">
                @csrf
                <x-field label="Phone" for="f-phone" :error="$errors->first('phone')">
                    <x-input id="f-phone" name="phone" required value="{{ old('phone') }}" />
                </x-field>
                <x-btn class="w-full">Send reset code</x-btn>
            </form>

            <p class="mt-4 text-center text-sm">
                <a href="{{ route('shop.login') }}" class="font-semibold text-emerald-700">Back to sign in</a>
            </p>
        </div>
    </div>
</x-store-layout>
