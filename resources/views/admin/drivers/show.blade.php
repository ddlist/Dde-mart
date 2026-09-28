{{-- DDE-Mart Admin — driver detail with documents + transitions (original view, UI kit) --}}
<x-admin-layout title="{{ $driver->name }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.drivers.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All drivers
        </a>
        <span @class([
            'badge', 'badge-amber' => $driver->status === 'pending', 'badge-green' => $driver->status === 'active',
            'badge-slate' => $driver->status === 'suspended', 'badge-red' => $driver->status === 'rejected',
        ])>{{ $driver->status }} · {{ $driver->kind }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Profile">
                <div class="flex items-center gap-4">
                    @if ($driver->photo_path)
                        <img src="{{ \App\Support\Images::url($driver->photo_path) }}" alt="" class="h-16 w-16 rounded-full object-cover">
                    @endif
                    <dl class="grid flex-1 grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-slate-400">Phone</dt><dd class="font-semibold">{{ $driver->phone ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Email</dt><dd class="font-semibold">{{ $driver->email ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Vehicle</dt><dd>{{ $driver->vehicle_info ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Zone / Store</dt><dd>{{ $driver->zone?->name ?? '—' }} / {{ $driver->store?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Fleet owner</dt><dd>{{ $driver->owner?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Bank</dt><dd>{{ $driver->bank_name ?? '—' }}{{ $driver->bank_account ? ' · '.$driver->bank_account : '' }}</dd></div>
                    </dl>
                </div>
                @if (auth()->user()->canAccess('drivers', 'edit'))
                    <div class="mt-3 flex gap-2">
                        <x-btn variant="row" href="{{ route('admin.drivers.edit', $driver) }}">Edit profile</x-btn>
                        <form method="POST" action="{{ route('admin.drivers.destroy', $driver) }}"
                              onsubmit="return confirm('Delete driver {{ $driver->name }}?')">
                            @csrf @method('DELETE')
                            <x-btn variant="row-danger">Delete</x-btn>
                        </form>
                    </div>
                @endif
            </x-card>

            <x-card title="Documents" sub="Approve or reject each submitted document.">
                @if ($driver->verifications->isEmpty())
                    <x-empty message="No documents submitted." />
                @else
                    <div class="space-y-4">
                        @foreach ($driver->verifications as $verification)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-bold">{{ $verification->type?->title ?? 'Document #'.$verification->id }}</p>
                                    <span @class([
                                        'badge', 'badge-amber' => $verification->status === 'pending',
                                        'badge-green' => $verification->status === 'approved', 'badge-red' => $verification->status === 'rejected',
                                    ])>{{ $verification->status }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach (['front_path' => 'Front', 'back_path' => 'Back'] as $field => $label)
                                        @if ($verification->{$field})
                                            <a href="{{ \App\Support\Images::url($verification->{$field}) }}" target="_blank" rel="noopener">
                                                <img src="{{ \App\Support\Images::url($verification->{$field}) }}" alt="{{ $label }}"
                                                     class="h-24 rounded-xl border border-slate-200 object-cover hover:opacity-90">
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                                @if ($verification->note)
                                    <p class="mt-2 text-xs text-slate-500">Note: {{ $verification->note }}
                                        @if ($verification->reviewer) · by {{ $verification->reviewer->name }} @endif</p>
                                @endif
                                @if (auth()->user()->canAccess('drivers', 'edit') && $verification->status === 'pending')
                                    <form method="POST" action="{{ route('admin.verifications.review', $verification) }}" class="mt-2 flex flex-wrap gap-2">
                                        @csrf
                                        <x-input name="note" placeholder="Review note (optional)" class="!w-auto min-w-52 flex-1" />
                                        <x-btn variant="row" name="to" value="approved">Approve</x-btn>
                                        <x-btn variant="row-danger" name="to" value="rejected">Reject</x-btn>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div>
            @if (auth()->user()->canAccess('drivers', 'edit'))
                <x-card title="Move driver">
                    @php $allowed = \App\Models\Driver::TRANSITIONS[$driver->status] ?? []; @endphp
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($allowed as $next)
                                <form method="POST" action="{{ route('admin.drivers.transition', $driver) }}">
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
