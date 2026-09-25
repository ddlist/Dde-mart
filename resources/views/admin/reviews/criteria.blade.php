{{-- DDE-Mart Admin — review criteria master (original view, UI kit) --}}
<x-admin-layout title="Review Criteria">
    <x-page-head title="Review Criteria" sub="Rating axes (distinct from catalog attributes)." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Add criterion">
            <form method="POST" action="{{ route('admin.review-criteria.store') }}" class="flex gap-2">
                @csrf
                <x-input name="title" required placeholder="e.g. Freshness" class="flex-1" />
                <x-btn>Add</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Title</th><th class="th text-right">Actions</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($criteria as $criterion)
                        <tr>
                            <td class="td font-semibold">{{ $criterion->title }}</td>
                            <td class="td text-right">
                                <form method="POST" action="{{ route('admin.review-criteria.destroy', $criterion) }}"
                                      onsubmit="return confirm('Delete criterion?')" class="inline">
                                    @csrf @method('DELETE')
                                    <x-btn variant="row-danger">Delete</x-btn>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="td"><x-empty message="No criteria yet." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $criteria->links() }}</div>
</x-admin-layout>
