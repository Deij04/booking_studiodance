<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');

        $users = User::query()
            ->when($request->user()->role === 'admin', fn ($query) => $query->where('role', 'customer'))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'managedUser' => new User(['role' => 'customer', 'status' => 'active']),
            'roles' => $this->roles($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);
        User::create($this->validated($request));

        return to_route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roles' => $this->roles($request),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $data = $this->validated($request, $user);

        if ($request->user()->is($user) && ($data['role'] !== $user->role || $data['status'] !== 'active')) {
            throw ValidationException::withMessages([
                'role' => 'Role dan status akun yang sedang digunakan tidak dapat diubah.',
            ]);
        }

        $user->update($data);

        return to_route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        // Riwayat transaksi tidak boleh ikut terhapus oleh cascade pada database.
        if ($user->bookings()->exists() || $user->classRegistrations()->exists() || $user->memberships()->exists()) {
            return to_route('admin.users.index')->with('error', 'User memiliki riwayat booking, kelas, atau membership. Nonaktifkan akun melalui Edit agar riwayat tetap tersimpan.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    private function roles(Request $request): array
    {
        return $request->user()->role === 'super_admin'
            ? ['customer' => 'User', 'admin' => 'Admin', 'super_admin' => 'Super Admin']
            : ['customer' => 'User'];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in(array_keys($this->roles($request)))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
