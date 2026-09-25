{{-- DDE-Mart Admin — new disbursement batch (original view, UI kit) --}}
<x-admin-layout title="New batch">
    <form method="POST" action="{{ route('admin.disbursements.store') }}" class="max-w-2xl space-y-5">
        @csrf

        <x-card title="New batch" sub="Only approved, unbatched payouts are listed.">
            <div class="space-y-4">
                <x-field label="Method" for="method">
                    <x-input id="method" name="method" value="bank" />
                </x-field>
                <x-field label="Note" for="note">
                    <x-input id="note" name="note" maxlength="1000" />
                </x-field>
                <div>
                    <span class="label">Payouts</span>
                    @if ($payouts->isEmpty())
                        <x-empty message="No approved payouts waiting." />
                    @else
                        <div class="max-h-80 space-y-1.5 overflow-y-auto">
                            @foreach ($payouts as $payout)
                                <label class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" name="payouts[]" value="{{ $payout->id }}" checked class="check">
                                        #{{ $payout->id }} · {{ $payout->requester_name }} ({{ $payout->requester_type }})
                                    </span>
                                    <span class="font-bold">{{ number_format($payout->amount, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('payouts')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>Create batch</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.disbursements.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
