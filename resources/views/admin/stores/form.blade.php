{{-- DDE-Mart Admin — store form + workflow (original view, UI kit) --}}
<x-admin-layout title="{{ $store->exists ? 'Manage store' : 'New store' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="Basics" sub="{{ $store->products_count ?? 0 }} product(s) assigned">
            <div class="space-y-4">
                <x-field label="Store name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $store->name) }}" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug" :error="$errors->first('slug')">
                    <x-input id="slug" name="slug" value="{{ old('slug', $store->slug) }}" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description">{{ old('description', $store->description) }}</x-textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Section" for="section_id">
                        <x-select id="section_id" name="section_id">
                            <option value="">— No section —</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) old('section_id', $store->section_id) === (string) $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Zone" for="zone_id">
                        <x-select id="zone_id" name="zone_id">
                            <option value="">— No zone —</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" @selected((string) old('zone_id', $store->zone_id) === (string) $zone->id)>{{ $zone->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Owner" for="owner_id">
                        <x-select id="owner_id" name="owner_id">
                            <option value="">— No owner —</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}" @selected((string) old('owner_id', $store->owner_id) === (string) $owner->id)>{{ $owner->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <x-field label="Photo" :error="$errors->first('image')">
                    @if ($store->image_path)
                        <img src="{{ \App\Support\Images::url($store->image_path) }}" alt="" class="mb-2 h-20 w-20 rounded-xl object-cover">
                        <div class="mb-2"><x-check name="remove_image" label="Remove current photo" /></div>
                    @endif
                    <input name="image" type="file" accept="image/*" class="file">
                </x-field>
            </div>
        </x-card>

        <x-card title="Owner & location">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Owner name" for="owner_name">
                    <x-input id="owner_name" name="owner_name" value="{{ old('owner_name', $store->owner_name) }}" />
                </x-field>
                <x-field label="Phone" for="phone">
                    <x-input id="phone" name="phone" value="{{ old('phone', $store->phone) }}" />
                </x-field>
            </div>
            <div class="mt-4">
                <x-field label="Address" for="address">
                    <x-input id="address" name="address" value="{{ old('address', $store->address) }}" />
                </x-field>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-field label="Latitude" for="latitude" :error="$errors->first('latitude')">
                    <x-input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $store->latitude) }}" />
                </x-field>
                <x-field label="Longitude" for="longitude" :error="$errors->first('longitude')">
                    <x-input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $store->longitude) }}" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Commercial">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Commission type" for="commission_type">
                    <x-select id="commission_type" name="commission_type">
                        <option value="percentage" @selected(old('commission_type', $store->commission_type ?? 'percentage') === 'percentage')>Percentage</option>
                        <option value="fixed" @selected(old('commission_type', $store->commission_type) === 'fixed')>Fixed</option>
                    </x-select>
                </x-field>
                <x-field label="Commission value" for="commission_value" :error="$errors->first('commission_value')">
                    <x-input id="commission_value" name="commission_value" type="number" step="0.01" min="0" required value="{{ old('commission_value', $store->commission_value ?? 0) }}" />
                </x-field>
                <x-field label="Min. order" for="min_order">
                    <x-input id="min_order" name="min_order" type="number" step="0.01" min="0" value="{{ old('min_order', $store->min_order ?? 0) }}" />
                </x-field>
                <x-field label="Subscription plan" for="subscription_plan_id">
                    <x-select id="subscription_plan_id" name="subscription_plan_id">
                        <option value="">— None —</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('subscription_plan_id', $store->subscription_plan_id) === (string) $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Delivery fee" for="delivery_fee">
                    <x-input id="delivery_fee" name="delivery_fee" type="number" step="0.01" min="0" value="{{ old('delivery_fee', $store->delivery_fee ?? 0) }}" />
                </x-field>
                <x-field label="Delivery per km" for="delivery_per_km">
                    <x-input id="delivery_per_km" name="delivery_per_km" type="number" step="0.01" min="0" value="{{ old('delivery_per_km', $store->delivery_per_km ?? 0) }}" />
                </x-field>
            </div>
            <div class="mt-4 flex gap-5">
                <x-check name="is_open" value="1" label="Open for orders" :checked="old('is_open', $store->is_open ?? true)" />
                <x-check name="self_delivery" value="1" label="Self delivery" :checked="old('self_delivery', $store->self_delivery ?? false)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $store->exists ? 'Save changes' : 'Create store' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.stores.index') }}">Cancel</x-btn>
        </div>
    </form>

    @if ($store->exists && auth()->user()->canAccess('stores', 'edit'))
        <x-card title="Status: {{ ucfirst($store->status) }}" sub="Only legal moves are shown" class="mt-5 max-w-3xl">
            <div class="flex flex-wrap gap-2">
                @forelse (\App\Models\Store::TRANSITIONS[$store->status] ?? [] as $next)
                    <form method="POST" action="{{ route('admin.stores.transition', $store) }}">
                        @csrf
                        <input type="hidden" name="to" value="{{ $next }}">
                        <x-btn variant="dark">Move to {{ $next }}</x-btn>
                    </form>
                @empty
                    <p class="text-sm text-slate-400">Terminal state.</p>
                @endforelse
            </div>
        </x-card>
    @endif
</x-admin-layout>
