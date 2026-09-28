<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — customer validation (original). Phone is the identity. */
class SaveCustomerRequest extends CatalogRequest
{
    protected string $group = 'users';

    public function rules(): array
    {
        $ignore = $this->currentId();

        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:50', Rule::unique('customers', 'phone')->ignore($ignore)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($ignore)],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
