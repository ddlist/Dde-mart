{{-- DDE-Mart storefront — reset password (original view) --}}
<x-store-layout title="Reset password">
    <div class="mx-auto w-full max-w-sm">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-lg font-black tracking-tight">Reset password</h1>
            <p class="mb-5 text-sm text-slate-500">Enter the code plus your new password.</p>

            @if (session('error'))
                <div class="alert-err">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert-ok">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('shop.reset.store') }}" class="space-y-4">
                @csrf
                <x-field label="Phone" for="r-phone" :error="$errors->first('phone')">
                    <x-input id="r-phone" name="phone" required value="{{ old('phone', $phone) }}" />
                </x-field>
                <x-field label="Reset code" for="r-code" :error="$errors->first('code')">
                    <x-input id="r-code" name="code" required inputmode="numeric" />
                </x-field>
                <x-field label="New password (min 8)" for="r-pass" :error="$errors->first('password')">
                    <x-input id="r-pass" name="password" type="password" required />
                </x-field>
                <x-field label="Confirm password" for="r-pass2">
                    <x-input id="r-pass2" name="password_confirmation" type="password" required />
                </x-field>
                <x-btn class="w-full">Set new password</x-btn>
            </form>
        </div>
    </div>
</x-store-layout>
