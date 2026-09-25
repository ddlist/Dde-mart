<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — role + permission-matrix management (original controller).
 * Inspired by: legacy RoleController (index/save/edit/store/update/delete).
 * Fixes: validation, DELETE verb, super-role protection, and — critically —
 * deleting a role with users attached is BLOCKED (legacy deleted those users).
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new Role(),
            'granted' => [],
            'catalog' => config('admin_permissions'),
            'method' => 'POST',
            'action' => route('admin.roles.store'),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->string('name'),
            'slug' => Str::slug($request->string('name')),
        ]);

        $role->syncAbilities($this->flattenAbilities($request->input('abilities', [])));

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' created.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'granted' => $role->abilityKeys(),
            'catalog' => config('admin_permissions'),
            'method' => 'PUT',
            'action' => route('admin.roles.update', $role),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->is_super) {
            return redirect()->route('admin.roles.index')->with('error', 'The Super Admin role cannot be modified.');
        }

        $role->update(['name' => $request->string('name')]);
        $role->syncAbilities($this->flattenAbilities($request->input('abilities', [])));

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_super) {
            return redirect()->route('admin.roles.index')->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')->with(
                'error',
                "Cannot delete '{$role->name}': {$role->users()->count()} user(s) still use it. Reassign them first."
            );
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' deleted.");
    }

    /** Convert abilities[group][] matrix into ["<group>.<ability>"] keys. */
    protected function flattenAbilities(array $matrix): array
    {
        $keys = [];

        foreach ($matrix as $group => $abilities) {
            foreach ((array) $abilities as $ability) {
                $keys[] = "{$group}.{$ability}";
            }
        }

        return $keys;
    }
}
