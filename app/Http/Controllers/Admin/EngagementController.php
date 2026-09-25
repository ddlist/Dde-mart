<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\OnboardingSlide;
use App\Models\ScheduledNotification;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — engagement extras (original controller).
 * Onboarding slides, homepage/footer content blocks, scheduled pushes.
 */
class EngagementController extends Controller
{
    // -- Onboarding ------------------------------------------------------

    public function slides(): View
    {
        $slides = OnboardingSlide::orderBy('sort_order')->paginate(15);

        return view('admin.content.slides', compact('slides'));
    }

    public function slideStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'audience' => ['required', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['image_path'] = ImageUploads::store($request->file('image'), 'onboarding');
        OnboardingSlide::create($validated);

        return redirect()->route('admin.slides.index')->with('success', 'Slide added.');
    }

    public function slideDestroy(OnboardingSlide $slide): RedirectResponse
    {
        ImageUploads::delete($slide->image_path);
        $slide->delete();

        return redirect()->route('admin.slides.index')->with('success', 'Slide deleted.');
    }

    // -- Content blocks --------------------------------------------------

    public function blocks(): View
    {
        $blocks = ContentBlock::orderBy('key')->get();

        return view('admin.content.blocks', compact('blocks'));
    }

    public function blockUpdate(Request $request, ContentBlock $block): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $block->update($validated);

        return redirect()->route('admin.blocks.index')->with('success', "Block '{$block->key}' saved.");
    }

    // -- Scheduled pushes -------------------------------------------------

    public function scheduled(): View
    {
        $scheduled = ScheduledNotification::orderBy('send_at')->paginate(15);

        return view('admin.content.scheduled', compact('scheduled'));
    }

    public function scheduleStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audience' => ['required', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
            'send_at' => ['required', 'date', 'after:now'],
        ]);

        $validated['created_by'] = $request->user()->id;
        ScheduledNotification::create($validated);

        return redirect()->route('admin.scheduled.index')->with('success', 'Push scheduled.');
    }

    public function scheduleDestroy(ScheduledNotification $scheduled): RedirectResponse
    {
        if ($scheduled->status !== 'scheduled') {
            return redirect()->route('admin.scheduled.index')->with('error', 'Only pending items can be cancelled.');
        }

        $scheduled->delete();

        return redirect()->route('admin.scheduled.index')->with('success', 'Scheduled push cancelled.');
    }
}
