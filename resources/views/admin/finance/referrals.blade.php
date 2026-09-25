{{-- DDE-Mart Admin — referrals list (original view, UI kit, read-only) --}}
<x-admin-layout title="Referrals">
    <x-page-head title="Referrals" sub="Signup incentive pairs." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.referrals.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search code or referrer…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Code</th><th class="th">Referred by</th><th class="th">Created</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($referrals as $referral)
                    <tr>
                        <td class="td font-mono font-bold">{{ $referral->code }}</td>
                        <td class="td font-mono text-xs text-slate-500">{{ $referral->referrer_ref ?? '—' }}</td>
                        <td class="td text-xs text-slate-500">{{ $referral->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="td"><x-empty message="No referrals yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $referrals->links() }}</div>
</x-admin-layout>
