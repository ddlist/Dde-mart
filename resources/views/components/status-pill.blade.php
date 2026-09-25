{{-- DDE-Mart Admin — active/draft pill (original component) --}}
@props(['active'])

<span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase {{ $active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500' }}">
    {{ $active ? 'Active' : 'Hidden' }}
</span>
