<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
 * DDE-Mart Admin — role update validation (original request).
 */
class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('roles', 'edit') ?? false;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($roleId)],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['array'],
            'abilities.*.*' => ['string', 'max:50'],
        ];
    }
}
