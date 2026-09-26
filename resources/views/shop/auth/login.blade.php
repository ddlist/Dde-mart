{{-- DDE-Mart storefront — sign in (original view) --}}
<x-store-layout title="Sign in">
    <div class="mx-auto w-full max-w-sm">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-lg font-black tracking-tight">Welcome back</h1>
            <p class="mb-5 text-sm text-slate-500">Password, or a code by SMS.</p>

            @if (session('error'))
                <div class="alert-err">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('shop.login.attempt') }}" class="space-y-4">
                @csrf
                <x-field label="Phone" for="phone">
                    <x-input id="phone" name="phone" required value="{{ old('phone') }}" />
                </x-field>
                <x-field label="Password" for="password">
                    <x-input id="password" name="password" type="password" required />
                </x-field>
                <x-btn class="w-full">Sign in</x-btn>
            </form>

            <div class="my-4 flex items-center gap-2 text-xs text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span> or <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <form method="POST" action="{{ route('shop.login.otp') }}" class="flex gap-2">
                @csrf
                <x-input name="phone" required placeholder="Phone for code" class="flex-1" />
                <x-btn variant="dark">Send code</x-btn>
            </form>

            <p class="mt-4 text-center text-sm text-slate-500">
                New here? <a href="{{ route('shop.register') }}" class="font-bold text-emerald-700">Create account</a>
                · <a href="{{ route('shop.forgot') }}" class="font-bold text-emerald-700">Forgot password</a>
            </p>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold">Have a code?</h2>
            <form method="POST" action="{{ route('shop.login.otp.verify') }}" class="mt-3 space-y-3">
                @csrf
                <x-input name="phone" required placeholder="Same phone" />
                <x-input name="code" required placeholder="6-digit code" />
                <x-btn variant="dark" class="w-full">Verify & sign in</x-btn>
            </form>
        </div>
    </div>
</x-store-layout>
