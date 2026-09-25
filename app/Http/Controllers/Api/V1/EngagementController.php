<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Banner;
use App\Models\Language;
use App\Models\Notification;
use App\Models\OnboardingSlide;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Images;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — engagement feeds (original). Public content + account push tokens.
 * Settings expose only the allowlisted non-secret ops knobs (same rule as panel).
 */
class EngagementController extends Controller
{
    public function notifications(Request $request)
    {
        $feed = Notification::whereIn('audience', ['customer', 'all'])
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $feed->map(fn ($n) => [
                'id' => $n->id,
                'subject' => $n->subject,
                'message' => $n->message,
                'at' => $n->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $feed->currentPage(),
                'last_page' => $feed->lastPage(),
                'total' => $feed->total(),
            ],
        ]);
    }

    public function pages()
    {
        return response()->json(['data' => Page::where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json(['data' => [
            'id' => $page->id, 'name' => $page->name,
            'slug' => $page->slug, 'body' => $page->body,
        ]]);
    }

    public function banners()
    {
        return response()->json(['data' => Banner::where('is_active', true)
            ->orderBy('sort_order')->get()->map(fn ($b) => [
                'id' => $b->id, 'title' => $b->title,
                'image' => Images::url($b->image_path),
                'redirect_type' => $b->redirect_type,
                'redirect_target' => $b->redirect_target,
                'position' => $b->position,
            ])]);
    }

    public function ads()
    {
        return response()->json(['data' => Advertisement::where('status', 'active')
            ->orderByDesc('priority')->limit(20)->get()->map(fn ($ad) => [
                'id' => $ad->id, 'title' => $ad->title,
                'description' => $ad->description,
                'cover' => Images::url($ad->cover_path),
                'video_url' => $ad->video_url,
            ])]);
    }

    public function settings()
    {
        return response()->json(['data' => Setting::allMerged()]);
    }

    public function onboarding(Request $request)
    {
        $slides = OnboardingSlide::where('is_active', true)
            ->when($request->filled('audience'), fn ($q) => $q->where('audience', $request->input('audience')))
            ->orderBy('sort_order')->get()->map(fn ($s) => [
                'id' => $s->id, 'title' => $s->title,
                'description' => $s->description,
                'image' => Images::url($s->image_path),
                'audience' => $s->audience,
            ]);

        return response()->json(['data' => $slides]);
    }

    public function languages()
    {
        return response()->json(['data' => Language::where('is_active', true)
            ->orderByDesc('is_default')->orderBy('code')->get(['id', 'code', 'name', 'is_default'])]);
    }

    public function registerToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        $user = $request->user();

        $user->pushTokens()->updateOrCreate(
            ['token' => $validated['token']],
            ['platform' => $validated['platform'] ?? 'android'],
        );

        return response()->json(['data' => ['registered' => true]]);
    }

    public function unregisterToken(Request $request)
    {
        $validated = $request->validate(['token' => ['required', 'string', 'max:500']]);

        $request->user()->pushTokens()->where('token', $validated['token'])->delete();

        return response()->json(['data' => ['unregistered' => true]]);
    }
}
