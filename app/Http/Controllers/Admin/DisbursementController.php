<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\PayoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — disbursement batches (original controller).
 * A batch groups approved payouts; marking paid cascades to members.
 */
class DisbursementController extends Controller
{
    public function index(): View
    {
        $batches = Disbursement::with(['handler'])->withCount('payouts')
            ->orderByDesc('id')->paginate(15);

        return view('admin.finance.disbursements', compact('batches'));
    }

    public function create(): View
    {
        $payouts = PayoutRequest::where('status', 'approved')
            ->whereDoesntHave('disbursements')
            ->orderByDesc('id')->limit(100)->get();

        return view('admin.finance.disbursement-form', compact('payouts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payouts' => ['required', 'array', 'min:1'],
            'payouts.*' => ['integer', 'exists:payout_requests,id'],
        ]);

        $batch = Disbursement::create([
            'method' => $validated['method'] ?? 'bank',
            'note' => $validated['note'] ?? null,
            'handled_by' => $request->user()->id,
        ]);

        $approved = PayoutRequest::whereIn('id', $validated['payouts'])
            ->where('status', 'approved')->pluck('id');
        $batch->payouts()->sync($approved);

        return redirect()->route('admin.disbursements.show', $batch)
            ->with('success', "Batch #{$batch->id} created with {$approved->count()} payout(s).");
    }

    public function show(Disbursement $disbursement): View
    {
        $disbursement->load(['payouts', 'handler']);

        return view('admin.finance.disbursement-show', ['batch' => $disbursement]);
    }

    public function markPaid(Request $request, Disbursement $disbursement): RedirectResponse
    {
        if ($disbursement->status !== 'pending') {
            return redirect()->route('admin.disbursements.show', $disbursement)
                ->with('error', 'Only pending batches can be paid.');
        }

        $disbursement->markPaid($request->user());

        return redirect()->route('admin.disbursements.show', $disbursement)
            ->with('success', "Batch #{$disbursement->id} marked paid.");
    }
}
