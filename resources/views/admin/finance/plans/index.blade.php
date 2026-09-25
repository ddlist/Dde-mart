{{-- DDE-Mart Admin — subscription plans list (original view, UI kit) --}}
<x-admin-layout title="Subscription Plans">
    <x-page-head title="Subscription Plans" sub="Vendor billing tiers.">
        <x-slot:action>
            @if (auth()->user()->canAccess('finance', 'create'))
                <x-btn href="{{ route('admin.plans.create') }}"><x-icon name="plus" class="h-4 w-4" /> New plan</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($plans as $plan)
            <x-card>
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-bold">{{ $plan->name }}</h3>
                        <p class="text-xs uppercase tracking-wider text-slate-400">{{ $plan->type }} · {{ $plan->validity_days }} days</p>
                    </div>
                    <x-status-pill :active="$plan->is_active" />
                </div>
                <p class="mt-2 text-3xl font-black">{{ number_format($plan->price, 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Items: {{ $plan->item_limit ?? '∞' }} · Orders: {{ $plan->order_limit ?? '∞' }}
                    @if ($plan->is_commission_plan) · commission @endif
                </p>
                @if ($plan->features)
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($plan->features as $feature)
                            <span class="badge-slate">{{ $feature }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="mt-4 flex gap-2">
                    @if (auth()->user()->canAccess('finance', 'edit'))
                        <x-btn variant="row" href="{{ route('admin.plans.edit', $plan) }}">Edit</x-btn>
                    @endif
                    @if (auth()->user()->canAccess('finance', 'delete'))
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                              onsubmit="return confirm('Delete plan {{ $plan->name }}?')">
                            @csrf @method('DELETE')
                            <x-btn variant="row-danger">Delete</x-btn>
                        </form>
                    @endif
                </div>
            </x-card>
        @empty
            <div class="col-span-full"><x-empty message="No plans yet." /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $plans->links() }}</div>
</x-admin-layout>
