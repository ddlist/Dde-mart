<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/*
 * DDE-Mart Admin — role validation (original requests).
 * Legacy had no validation (empty role names allowed); rebuild requires a name
 * and restricts abilities to keys declared in config/admin_permissions.php.
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('roles', 'create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            // abilities[group][] = ability, e.g. abilities[orders][] = view
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['array'],
            'abilities.*.*' => ['string', 'max:50'],
        ];
    }
}
