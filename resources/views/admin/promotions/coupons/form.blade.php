{{-- DDE-Mart Admin — coupon form (original view, UI kit) --}}
<x-admin-layout title="{{ $coupon->exists ? 'Edit coupon' : 'New coupon' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $coupon->exists ? 'Edit coupon' : 'New coupon' }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Code" for="code" :error="$errors->first('code')">
                    <x-input id="code" name="code" required value="{{ old('code', $coupon->code) }}" placeholder="BIG20" class="font-mono uppercase" />
                </x-field>
                <x-field label="Scope" for="scope">
                    <x-select id="scope" name="scope">
                        @foreach (\App\Models\Coupon::SCOPES as $scope)
                            <option value="{{ $scope }}" @selected(old('scope', $coupon->scope ?? 'all') === $scope)>{{ ucfirst($scope) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
            <div class="mt-4">
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description" rows="2">{{ old('description', $coupon->description) }}</x-textarea>
                </x-field>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-field label="Discount type" for="discount_type">
                    <x-select id="discount_type" name="discount_type">
                        @foreach (\App\Models\Coupon::TYPES as $type)
                            <option value="{{ $type }}" @selected(old('discount_type', $coupon->discount_type ?? 'percentage') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Discount value" for="discount_value" :error="$errors->first('discount_value')">
                    <x-input id="discount_value" name="discount_value" type="number" step="0.01" min="0" required value="{{ old('discount_value', $coupon->discount_value) }}" />
                </x-field>
                <x-field label="Min. order" for="min_order">
                    <x-input id="min_order" name="min_order" type="number" step="0.01" min="0" value="{{ old('min_order', $coupon->min_order ?? 0) }}" />
                </x-field>
                <x-field label="Max discount" for="max_discount">
                    <x-input id="max_discount" name="max_discount" type="number" step="0.01" min="0" value="{{ old('max_discount', $coupon->max_discount) }}" />
                </x-field>
                <x-field label="Usage limit" for="usage_limit">
                    <x-input id="usage_limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $coupon->usage_limit) }}" />
                </x-field>
                <x-field label="Section" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— All —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $coupon->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Starts" for="starts_at">
                    <x-input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" />
                </x-field>
                <x-field label="Expires" for="expires_at" :error="$errors->first('expires_at')">
                    <x-input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" />
                </x-field>
            </div>
            <div class="mt-4">
                @include('admin.catalog.partials.image-field', ['model' => $coupon])
            </div>
            <div class="mt-4 flex gap-5">
                <x-check name="is_public" label="Public" :checked="old('is_public', $coupon->is_public ?? true)" />
                <x-check name="is_active" label="Active" :checked="old('is_active', $coupon->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $coupon->exists ? 'Save changes' : 'Create coupon' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.coupons.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
