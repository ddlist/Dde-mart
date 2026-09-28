{{-- DDE-Mart Admin — payment summary per role (original view, UI kit) --}}
<x-admin-layout title="Payment summary">
    <x-page-head title="Payment summary" sub="Wallet balance, pending and paid payouts per audience." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($summary as $role => $row)
            <x-card :title="ucfirst($role)">
                <p class="mt-2 text-3xl font-black">{{ number_format($row['wallet'], 2) }}</p>
                <p class="hint">wallet balance</p>
                <dl class="mt-3 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Pending</dt><dd>{{ number_format($row['pending'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ number_format($row['paid'], 2) }}</dd></div>
                </dl>
            </x-card>
        @endforeach
    </div>
</x-admin-layout>
