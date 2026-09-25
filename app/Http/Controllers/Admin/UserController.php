<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — staff user management (original controller).
 * Inspired by: legacy UserController admin-users section — reimplemented.
 * Guards (legacy lacked all three): no self-delete, no deleting the last
 * super-admin, no changing your own role.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('role')
            ->when($request->string('search'), function ($query, string $search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->integer('role'), fn ($query, int $roleId) => $query->where('role_id', $roleId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'staff' => new User(),
            'roles' => Role::orderBy('name')->get(),
            'method' => 'POST',
            'action' => route('admin.users.store'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $staff = User::create($request->validated());

        return redirect()->route('admin.users.index')->with('success', "User '{$staff->name}' created.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'staff' => $user,
            'roles' => Role::orderBy('name')->get(),
            'method' => 'PUT',
            'action' => route('admin.users.update', $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Nobody may change their own role (prevents accidental lockout).
        if ($user->is($request->user())) {
            unset($data['role_id']);
        }

        // Never allow a blank password to overwrite the hash.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && User::whereHas('role', fn ($q) => $q->where('is_super', true))->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete the last super-admin.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' deleted.");
    }
}
