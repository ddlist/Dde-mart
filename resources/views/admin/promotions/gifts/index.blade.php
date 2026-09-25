{{-- DDE-Mart Admin — gift cards list (original view, UI kit) --}}
<x-admin-layout title="Gift Cards">
    <x-page-head title="Gift Cards" sub="Prepaid value cards.">
        <x-slot:action>
            @if (auth()->user()->canAccess('promotions', 'create'))
                <x-btn href="{{ route('admin.gifts.create') }}"><x-icon name="plus" class="h-4 w-4" /> New gift card</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.gifts.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search gift cards…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Card</th><th class="th">Amount</th><th class="th">Validity</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($cards as $card)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($card->image_path)
                                    <img src="{{ \App\Support\Images::url($card->image_path) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                                @endif
                                <p class="font-semibold">{{ $card->title }}</p>
                            </div>
                        </td>
                        <td class="td font-semibold">{{ number_format($card->amount, 2) }}</td>
                        <td class="td text-xs text-slate-500">{{ $card->expiry_days }} days</td>
                        <td class="td"><x-status-pill :active="$card->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('promotions', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.gifts.edit', $card) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('promotions', 'delete'))
                                    <form method="POST" action="{{ route('admin.gifts.destroy', $card) }}"
                                          onsubmit="return confirm('Delete gift card {{ $card->title }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No gift cards yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cards->links() }}</div>
</x-admin-layout>
