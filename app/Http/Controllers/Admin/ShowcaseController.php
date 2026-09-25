<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FilterPreset;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — stories moderation + filter presets (original controller).
 */
class ShowcaseController extends Controller
{
    public function stories(Request $request): View
    {
        $stories = Story::with(['store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.content.stories', compact('stories'));
    }

    public function storyToggle(Story $story): RedirectResponse
    {
        $story->update(['status' => $story->status === 'active' ? 'removed' : 'active']);

        return redirect()->route('admin.stories.index')->with('success', "Story {$story->status}.");
    }

    public function storyDestroy(Story $story): RedirectResponse
    {
        $story->delete();

        return redirect()->route('admin.stories.index')->with('success', 'Story deleted.');
    }

    public function presets(): View
    {
        $presets = FilterPreset::orderBy('name')->paginate(20);

        return view('admin.content.presets', compact('presets'));
    }

    public function presetStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:filter_presets,name']]);
        FilterPreset::create($data);

        return redirect()->route('admin.presets.index')->with('success', 'Preset added.');
    }

    public function presetDestroy(FilterPreset $preset): RedirectResponse
    {
        $preset->delete();

        return redirect()->route('admin.presets.index')->with('success', 'Preset deleted.');
    }
}
