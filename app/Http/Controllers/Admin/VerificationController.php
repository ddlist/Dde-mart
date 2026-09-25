<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDocumentTypeRequest;
use App\Models\DocumentType;
use App\Models\Verification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — document types + verification review (original controller).
 * Review actions flip a single verification row; the queue view groups pendings first.
 */
class VerificationController extends Controller
{
    public function types(): View
    {
        $types = DocumentType::withCount('verifications')->orderBy('title')->paginate(15);

        return view('admin.verification.types', compact('types'));
    }

    public function typeCreate(): View
    {
        return view('admin.verification.type-form', $this->typeFormData(new DocumentType()));
    }

    public function typeStore(SaveDocumentTypeRequest $request): RedirectResponse
    {
        $type = DocumentType::create($this->typePayload($request));

        return redirect()->route('admin.doc-types.index')->with('success', "Document '{$type->title}' created.");
    }

    public function typeEdit(DocumentType $documentType): View
    {
        return view('admin.verification.type-form', $this->typeFormData($documentType));
    }

    public function typeUpdate(SaveDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->update($this->typePayload($request));

        return redirect()->route('admin.doc-types.index')->with('success', "Document '{$documentType->title}' updated.");
    }

    public function typeDestroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->verifications()->exists()) {
            return redirect()->route('admin.doc-types.index')->with(
                'error', "Cannot delete '{$documentType->title}': verification history references it."
            );
        }

        $documentType->delete();

        return redirect()->route('admin.doc-types.index')->with('success', "Document '{$documentType->title}' deleted.");
    }

    public function queue(Request $request): View
    {
        $verifications = Verification::with(['verifiable', 'type', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.verification.queue', compact('verifications'));
    }

    public function review(Request $request, Verification $verification): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $verification->update([
            'status' => $validated['to'],
            'note' => $validated['note'] ?? $verification->note,
            'reviewed_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', "Document {$validated['to']}.");
    }

    protected function typeFormData(DocumentType $type): array
    {
        return [
            'type' => $type,
            'method' => $type->exists ? 'PUT' : 'POST',
            'action' => $type->exists
                ? route('admin.doc-types.update', $type)
                : route('admin.doc-types.store'),
        ];
    }

    protected function typePayload(SaveDocumentTypeRequest $request): array
    {
        $data = $request->validated();
        $data['front_required'] = $request->boolean('front_required');
        $data['back_required'] = $request->boolean('back_required');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
