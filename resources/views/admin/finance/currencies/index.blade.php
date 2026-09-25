{{-- DDE-Mart Admin — currencies list (original view, UI kit) --}}
<x-admin-layout title="Currencies">
    <x-page-head title="Currencies" sub="Exactly one default.">
        <x-slot:action>
            @if (auth()->user()->canAccess('finance', 'create'))
                <x-btn href="{{ route('admin.currencies.create') }}"><x-icon name="plus" class="h-4 w-4" /> New currency</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Code</th><th class="th">Name</th><th class="th">Symbol</th><th class="th">Format</th><th class="th">Default</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($currencies as $currency)
                    <tr>
                        <td class="td font-mono font-bold">{{ $currency->code }}</td>
                        <td class="td">{{ $currency->name }}</td>
                        <td class="td">{{ $currency->symbol }}</td>
                        <td class="td text-slate-500">{{ $currency->format(1234.5) }}</td>
                        <td class="td">
                            @if ($currency->is_default)
                                <span class="badge-ink">default</span>
                            @endif
                        </td>
                        <td class="td"><x-status-pill :active="$currency->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('finance', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.currencies.edit', $currency) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('finance', 'delete') && ! $currency->is_default)
                                    <form method="POST" action="{{ route('admin.currencies.destroy', $currency) }}"
                                          onsubmit="return confirm('Delete currency {{ $currency->code }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td"><x-empty message="No currencies yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $currencies->links() }}</div>
</x-admin-layout>
