<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemReview;
use App\Models\ReviewCriterion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — review moderation + criteria master (original controller).
 * Approved reviews will feed computed product ratings (replacing stored aggregates).
 */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = ItemReview::with(['product', 'store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->input('rating')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function moderate(Request $request, ItemReview $review): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'in:approved,rejected']])['to'];
        $review->update(['status' => $to]);

        return redirect()->route('admin.reviews.index')->with('success', "Review {$to}.");
    }

    public function destroy(ItemReview $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted.');
    }

    public function criteria(): View
    {
        $criteria = ReviewCriterion::orderBy('title')->paginate(15);

        return view('admin.reviews.criteria', compact('criteria'));
    }

    public function criteriaStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150']]);
        ReviewCriterion::create($data);

        return redirect()->route('admin.review-criteria.index')->with('success', 'Criterion added.');
    }

    public function criteriaDestroy(ReviewCriterion $criterion): RedirectResponse
    {
        $criterion->delete();

        return redirect()->route('admin.review-criteria.index')->with('success', 'Criterion deleted.');
    }
}
