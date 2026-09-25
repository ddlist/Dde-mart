{{-- DDE-Mart Admin — review moderation inbox (original view, UI kit) --}}
<x-admin-layout title="Reviews">
    <x-page-head title="Reviews" sub="Approve to publish; rejections stay hidden.">
        <x-slot:action>
            <x-btn variant="ghost" href="{{ route('admin.review-criteria.index') }}">Criteria</x-btn>
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\ItemReview::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-select name="rating" onchange="this.form.submit()">
                <option value="">All ratings</option>
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected((string) request('rating') === (string) $i)>{{ $i }}★</option>
                @endfor
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Review</th><th class="th">Target</th><th class="th">Rating</th><th class="th">Status</th><th class="th text-right">Moderate</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($reviews as $review)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $review->author_name ?? 'Anonymous' }}</p>
                            <p class="max-w-md text-xs text-slate-500">{{ $review->comment }}</p>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $review->product?->name ?? '—' }}<span class="block">{{ $review->store?->name ?? '' }}</span></td>
                        <td class="td font-bold">{{ $review->rating }}★</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $review->status === 'pending',
                                'badge-green' => $review->status === 'approved', 'badge-red' => $review->status === 'rejected',
                            ])>{{ $review->status }}</span>
                        </td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('orders', 'edit'))
                                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                                        @csrf
                                        <x-btn variant="row" name="to" value="approved">Approve</x-btn>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                                        @csrf
                                        <x-btn variant="row-danger" name="to" value="rejected">Reject</x-btn>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}"
                                          onsubmit="return confirm('Delete this review?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No reviews." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reviews->links() }}</div>
</x-admin-layout>
