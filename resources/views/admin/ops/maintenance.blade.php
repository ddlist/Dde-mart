{{-- DDE-Mart Admin — maintenance mode (original view) --}}
<x-admin-layout title="Maintenance">
    <x-page-head title="Maintenance" sub="Take the panel offline for deploys." />

    <x-card title="Status" class="max-w-xl">
        <p class="text-sm">
            The panel is currently
            <span @class(['badge', 'badge-green' => ! $down, 'badge-red' => $down])>{{ $down ? 'in maintenance' : 'live' }}</span>
        </p>
        <form method="POST" action="{{ route('admin.ops.maintenance.toggle') }}" class="mt-3">
            @csrf
            <input type="hidden" name="mode" value="{{ $down ? 'up' : 'down' }}">
            <x-btn variant="{{ $down ? 'primary' : 'dark' }}">{{ $down ? 'Bring back online' : 'Take offline' }}</x-btn>
        </form>
        @if ($down)
            <p class="hint mt-2">Bypass with the <code>dde-ops</code> secret URL while offline.</p>
        @endif
    </x-card>
</x-admin-layout>
