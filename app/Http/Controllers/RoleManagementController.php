<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query()->with('permissions')->withCount('users');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('permission')) {
            $permission = trim((string) $request->input('permission'));
            $query->whereHas('permissions', function ($builder) use ($permission) {
                $builder->where('name', $permission)->where('guard_name', 'web');
            });
        }

        $roles = $query->orderBy('name')->paginate(20)->appends($request->query());
        $permissions = Permission::query()->orderBy('name')->get();
        $permissionGroups = $permissions->groupBy(function (Permission $permission) {
            $prefix = Str::before($permission->name, '.');

            return Str::headline($prefix !== '' ? $prefix : $permission->name);
        });
        $summary = [
            'total_roles' => Role::query()->count(),
            'total_permissions' => Permission::query()->count(),
            'assigned_users' => Role::query()->withCount('users')->get()->sum('users_count'),
        ];

        return view('settings.roles.index', compact('roles', 'permissions', 'permissionGroups', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->route('settings.roles.index')->with('error', 'Role admin tidak boleh dihapus.');
        }

        $role->delete();

        return redirect()->route('settings.roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
