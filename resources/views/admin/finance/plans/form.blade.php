{{-- DDE-Mart Admin — subscription plan form (original view, UI kit) --}}
<x-admin-layout title="{{ $plan->exists ? 'Edit plan' : 'New plan' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $plan->exists ? 'Edit plan' : 'New plan' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $plan->name) }}" placeholder="Bronze Plan" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description" rows="2">{{ old('description', $plan->description) }}</x-textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Type" for="type">
                        <x-select id="type" name="type">
                            <option value="free" @selected(old('type', $plan->type ?? 'free') === 'free')>Free</option>
                            <option value="paid" @selected(old('type', $plan->type) === 'paid')>Paid</option>
                        </x-select>
                    </x-field>
                    <x-field label="Price" for="price">
                        <x-input id="price" name="price" type="number" step="0.01" min="0" required value="{{ old('price', $plan->price ?? 0) }}" />
                    </x-field>
                    <x-field label="Validity (days)" for="validity_days">
                        <x-input id="validity_days" name="validity_days" type="number" min="1" required value="{{ old('validity_days', $plan->validity_days ?? 30) }}" />
                    </x-field>
                    <x-field label="Item limit (-1 = ∞)" for="item_limit">
                        <x-input id="item_limit" name="item_limit" type="number" min="-1" value="{{ old('item_limit', $plan->item_limit ?? -1) }}" />
                    </x-field>
                    <x-field label="Order limit (-1 = ∞)" for="order_limit">
                        <x-input id="order_limit" name="order_limit" type="number" min="-1" value="{{ old('order_limit', $plan->order_limit ?? -1) }}" />
                    </x-field>
                    <x-field label="Section (blank = all)" for="section_id">
                        <x-select id="section_id" name="section_id">
                            <option value="">— All sections —</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) old('section_id', $plan->section_id) === (string) $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <div>
                    <span class="label">Features</span>
                    <div class="flex flex-wrap gap-3">
                        @foreach (\App\Models\SubscriptionPlan::FEATURES as $feature)
                            <x-check name="features[]" :value="$feature" :label="$feature"
                                :checked="in_array($feature, old('features', $plan->features ?? []))" />
                        @endforeach
                    </div>
                </div>
                @include('admin.catalog.partials.image-field', ['model' => $plan])
                <div class="flex gap-5">
                    <x-check name="is_commission_plan" label="Commission plan" :checked="old('is_commission_plan', $plan->is_commission_plan ?? false)" />
                    <x-check name="is_active" label="Active" :checked="old('is_active', $plan->is_active ?? true)" />
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $plan->exists ? 'Save changes' : 'Create plan' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.plans.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
