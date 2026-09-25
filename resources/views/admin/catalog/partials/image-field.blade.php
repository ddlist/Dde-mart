{{-- DDE-Mart Admin — image upload field with preview (original partial, UI kit) --}}
@props(['model', 'label' => 'Image'])

<div>
    <span class="label">{{ $label }}</span>
    @if ($model->image_path)
        <img src="{{ \App\Support\Images::url($model->image_path) }}" alt="" class="mb-2 h-20 w-20 rounded-xl object-cover">
        <div class="mb-2"><x-check name="remove_image" label="Remove current image" /></div>
    @endif
    <input name="image" type="file" accept="image/*" class="file">
    @error('image')<p class="field-error">{{ $message }}</p>@enderror
</div>
