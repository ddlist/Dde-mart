{{-- DDE-Mart storefront — CMS page (original view) --}}
<x-store-layout title="{{ $page->name }}">
    <div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-black tracking-tight">{{ $page->name }}</h1>
        <div class="prose-slate mt-3 whitespace-pre-line text-sm text-slate-600">{{ $page->body }}</div>
    </div>
</x-store-layout>
