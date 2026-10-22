<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['admin', 'dosen', 'mahasiswa'])],
        ]);

        $query = User::query()->withCount(['taughtCourses', 'submissions']);

        if (! empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $query->where(function ($userQuery) use ($keyword): void {
                $userQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('nim_nip', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('users.index', compact('users', 'filters'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $role = $validated['role'];
        unset($validated['role']);

        $user = User::create($validated);
        $user->role = $role;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function show(User $user)
    {
        $user->loadCount(['taughtCourses', 'submissions']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($validated['password'] !== null) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $validated['password'];
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->tokens()->delete();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Kata sandi pengguna berhasil diatur ulang.');
    }

    public function destroy(User $user)
    {
        if ($user->taughtCourses()->exists() || $user->submissions()->exists()) {
            return redirect()->route('admin.users.index')->with('error', 'Pengguna tidak dapat dihapus karena masih memiliki data terkait.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
