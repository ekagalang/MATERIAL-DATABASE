<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $role = trim((string) $request->input('role'));
            $query->whereHas('roles', function ($builder) use ($role) {
                $builder->where('name', $role)->where('guard_name', 'web');
            });
        }

        $users = $query->orderBy('name')->paginate(20)->appends($request->query());
        $roles = Role::query()->withCount('users')->orderBy('name')->get();
        $registrationEnabled = AppSetting::getBool('auth.registration_enabled');
        $summary = [
            'total_users' => User::query()->count(),
            'with_roles' => User::query()->has('roles')->count(),
            'pending_access' => User::query()->doesntHave('roles')->count(),
        ];

        return view('settings.users.index', compact('users', 'roles', 'registrationEnabled', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('settings.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('settings.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('settings.users.index')->with('error', 'User aktif tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('settings.users.index')->with('success', 'User berhasil dihapus.');
    }

    public function updateRegistration(Request $request)
    {
        $validated = $request->validate([
            'registration_enabled' => ['nullable', 'boolean'],
        ]);

        AppSetting::putValue('auth.registration_enabled', !empty($validated['registration_enabled']));

        return redirect()->route('settings.users.index')->with('success', 'Pengaturan register berhasil diperbarui.');
    }
}
