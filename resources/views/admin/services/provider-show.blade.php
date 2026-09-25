{{-- DDE-Mart Admin — provider detail (original view, UI kit) --}}
<x-admin-layout title="{{ $provider->name }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.providers.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All providers
        </a>
        <span class="badge-sky">{{ $provider->status }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card title="Services ({{ $provider->services->count() }})">
            @if ($provider->services->isEmpty())
                <x-empty message="No services." />
            @else
                <ul class="space-y-1.5 text-sm">
                    @foreach ($provider->services as $service)
                        <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                            <span>{{ $service->title }} <span class="text-xs text-slate-400">· {{ $service->category?->title ?? '' }}</span></span>
                            <span class="flex items-center gap-2 font-bold">{{ number_format($service->price, 2) }}
                                <x-status-pill :active="$service->is_active" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div class="space-y-4">
            <x-card title="Workers ({{ $provider->workers->count() }})">
                @if ($provider->workers->isEmpty())
                    <x-empty message="No workers." />
                @else
                    <ul class="space-y-1.5 text-sm">
                        @foreach ($provider->workers as $worker)
                            <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                                <span>{{ $worker->name }} <span class="text-xs text-slate-400">· {{ $worker->phone }}</span></span>
                                <x-status-pill :active="$worker->is_active" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @if (auth()->user()->canAccess('transport', 'edit'))
                <x-card title="Move provider">
                    @php $allowed = \App\Models\Provider::TRANSITIONS[$provider->status] ?? []; @endphp
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($allowed as $next)
                                <form method="POST" action="{{ route('admin.providers.transition', $provider) }}">
                                    @csrf
                                    <input type="hidden" name="to" value="{{ $next }}">
                                    <x-btn variant="dark">Move to {{ $next }}</x-btn>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
