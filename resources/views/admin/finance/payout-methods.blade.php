{{-- DDE-Mart Admin — saved withdraw methods (original view, UI kit) --}}
<x-admin-layout title="Withdraw methods">
    <x-page-head title="Withdraw methods" sub="Saved payout destinations per requester." />

    @if (auth()->user()->canAccess('finance', 'edit'))
        <x-card title="Add method" class="mb-4">
            <form method="POST" action="{{ route('admin.payout-methods.store') }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <x-select name="requester_type" required>
                    @foreach (['driver', 'vendor', 'owner', 'provider', 'customer'] as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </x-select>
                <x-input name="requester_ref" placeholder="Owner ref (e.g. phone)" required class="min-w-44 flex-1" />
                <x-select name="method" required>
                    @foreach (\App\Models\PayoutMethod::METHODS as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                    @endforeach
                </x-select>
                <x-input name="bank_name" placeholder="Bank name" class="min-w-40 flex-1" />
                <x-input name="bank_account" placeholder="Account" class="min-w-40 flex-1" />
                <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="is_default" value="1"> Default</label>
                <x-btn>Save</x-btn>
            </form>
        </x-card>
    @endif

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Requester</th><th class="th">Method</th><th class="th">Details</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($methods as $method)
                    <tr>
                        <td class="td"><span class="font-semibold">{{ $method->requester_ref }}</span> <span class="badge-slate">{{ $method->requester_type }}</span></td>
                        <td class="td">{{ $method->method }}@if ($method->is_default) <span class="badge-green ml-1">default</span>@endif</td>
                        <td class="td text-slate-500">{{ $method->details['bank_name'] ?? '—' }}{{ isset($method->details['bank_account']) ? ' · '.$method->details['bank_account'] : '' }}</td>
                        <td class="td text-right">
                            @if (auth()->user()->canAccess('finance', 'edit'))
                                <form method="POST" action="{{ route('admin.payout-methods.destroy', $method) }}" onsubmit="return confirm('Remove this method?')">
                                    @csrf @method('DELETE')
                                    <x-btn variant="row-danger">Delete</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No saved methods." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $methods->links() }}</div>
</x-admin-layout>
