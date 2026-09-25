<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — email template validation (original). */
class SaveEmailTemplateRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', Rule::unique('email_templates', 'key')->ignore($this->currentId())],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
            'send_to_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
