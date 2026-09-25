<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — push template validation (original). */
class SavePushTemplateRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', Rule::unique('notification_templates', 'key')->ignore($this->currentId())],
            'audience' => ['required', Rule::in(Notification::AUDIENCES)],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
