{{-- DDE-Mart Admin — ops settings editor (original view, UI kit) --}}
<x-admin-layout title="Settings">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-5" enctype="multipart/form-data">
        @csrf @method('PUT')

        @foreach ($groups as $group => $meta)
            <x-card :title="$meta['label']">
                <div class="space-y-4">
                    @foreach ($meta['keys'] as $key)
                        @php($type = $types[$key]['type'] ?? 'text')
                        @php($help = $types[$key]['help'] ?? null)
                        @php($current = old('settings.'.$key, $values[$key] ?? ''))
                        @if ($type === 'bool')
                            <x-check name="settings[{{ $key }}]" :checked="$current === '1'" :label="ucwords(str_replace('_', ' ', $key))" />
                            @if ($help)<p class="hint">{{ $help }}</p>@endif
                        @elseif ($type === 'select')
                            <x-field :label="ucwords(str_replace('_', ' ', $key))" for="setting-{{ $key }}" :hint="$help">
                                <x-select id="setting-{{ $key }}" name="settings[{{ $key }}]">
                                    @foreach (($types[$key]['options'] ?? []) as $value => $label)
                                        <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-select>
                            </x-field>
                        @elseif ($type === 'file')
                            <x-field :label="ucwords(str_replace('_', ' ', $key))" for="setting-file-{{ $key }}" :hint="$help">
                                @if (!empty($current))
                                    <div class="mb-2">
                                        @if (($types[$key]['kind'] ?? 'image') === 'audio')
                                            <audio controls src="{{ asset('storage/'.$current) }}" class="w-full"></audio>
                                        @else
                                            <img src="{{ asset('storage/'.$current) }}" alt="" class="h-16 rounded" />
                                        @endif
                                    </div>
                                @endif
                                <x-input id="setting-file-{{ $key }}" type="file" name="files[{{ $key }}]" />
                            </x-field>
                        @else
                            <x-field :label="ucwords(str_replace('_', ' ', $key))" for="setting-{{ $key }}" :hint="$help">
                                <x-input id="setting-{{ $key }}" name="settings[{{ $key }}]" value="{{ $current }}" :type="$type === 'number' ? 'number' : 'text'" />
                            </x-field>
                        @endif
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <x-card title="Integrations status">
            <div class="space-y-2">
                @foreach ($integrations as $name => $ok)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="inline-block h-2.5 w-2.5 rounded-full {{ $ok ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                        <span>{{ $name }}</span>
                        <span class="text-slate-400">— {{ $ok ? 'configured' : 'missing' }}</span>
                    </div>
                @endforeach
                <p class="hint">Presence only — secrets live in <code>.env</code>, never here.</p>
            </div>
        </x-card>

        <div class="alert-warn">
            Payment gateway secrets are NOT managed here — they live in <code>.env</code> only.
        </div>

        <x-btn>Save settings</x-btn>
    </form>
</x-admin-layout>
