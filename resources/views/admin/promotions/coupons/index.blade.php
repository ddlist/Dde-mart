{{-- DDE-Mart Admin — coupons list (original view, UI kit) --}}
<x-admin-layout title="Coupons">
    <x-page-head title="Coupons" sub="One table for all scopes (food, parcel, rental).">
        <x-slot:action>
            @if (auth()->user()->canAccess('promotions', 'create'))
                <x-btn href="{{ route('admin.coupons.create') }}"><x-icon name="plus" class="h-4 w-4" /> New coupon</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.coupons.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search code…" class="min-w-52 flex-1" />
            <x-select name="scope" onchange="this.form.submit()">
                <option value="">All scopes</option>
                @foreach (\App\Models\Coupon::SCOPES as $scope)
                    <option value="{{ $scope }}" @selected(request('scope') === $scope)>{{ ucfirst($scope) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Code</th><th class="th">Discount</th><th class="th">Scope</th><th class="th">Usage</th><th class="th">Expires</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="td font-mono font-bold">{{ $coupon->code }}</td>
                        <td class="td">{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : $coupon->discount_value }}</td>
                        <td class="td text-xs text-slate-500">{{ $coupon->scope }}</td>
                        <td class="td text-xs text-slate-500">{{ $coupon->used_count }}{{ $coupon->usage_limit ? '/'.$coupon->usage_limit : '' }}</td>
                        <td class="td text-xs text-slate-500">{{ $coupon->expires_at?->format('d M Y') ?? '—' }}</td>
                        <td class="td"><x-status-pill :active="$coupon->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('promotions', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.coupons.edit', $coupon) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('promotions', 'delete'))
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td"><x-empty message="No coupons yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $coupons->links() }}</div>
</x-admin-layout>
