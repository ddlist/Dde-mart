<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
 * DDE-Mart Admin — shared catalog authorization (original base request).
 * POST → needs catalog.create, PUT/PATCH → needs catalog.edit.
 */
abstract class CatalogRequest extends FormRequest
{
    /** Ability group checked by admin.can (catalog, promotions, finance…). */
    protected string $group = 'catalog';

    public function authorize(): bool
    {
        $ability = $this->isMethod('post') ? 'create' : 'edit';

        return $this->user()?->canAccess($this->group, $ability) ?? false;
    }

    protected function uniqueOn(string $table, ?int $ignoreId = null): array
    {
        return ['string', 'max:150', Rule::unique($table, 'slug')->ignore($ignoreId)];
    }

    /** Id of the bound model being edited (any resource), for unique-ignore rules. */
    protected function currentId(): ?int
    {
        foreach ($this->route()->parameters() as $param) {
            if ($param instanceof \Illuminate\Database\Eloquent\Model && $param->exists) {
                return (int) $param->getKey();
            }
        }

        return null;
    }
}
