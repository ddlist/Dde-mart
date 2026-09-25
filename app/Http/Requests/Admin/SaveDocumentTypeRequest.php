<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentType;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — document type validation (original). */
class SaveDocumentTypeRequest extends CatalogRequest
{
    protected string $group = 'drivers';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'owner_type' => ['required', Rule::in(DocumentType::OWNERS)],
            'front_required' => ['nullable', 'boolean'],
            'back_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
