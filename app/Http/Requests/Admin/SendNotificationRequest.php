<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — broadcast validation (original). */
class SendNotificationRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function authorize(): bool
    {
        return $this->user()?->canAccess('content', 'create') ?? false;
    }

    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::in(Notification::AUDIENCES)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }
}
