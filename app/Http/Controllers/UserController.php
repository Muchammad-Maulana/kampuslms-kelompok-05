<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar role yang valid untuk pengguna.
     *
     * Catatan penting: kolom `role` SENGAJA TIDAK dimasukkan ke dalam
     * $fillable pada model App\Models\User. Artinya role tidak boleh
     * dan tidak akan pernah terisi lewat mass assignment (create()/fill()
     * dari input request secara langsung). Nilai role selalu diisi secara
     * eksplisit oleh controller ini (lihat store() dan update()) agar
     * pengguna tidak bisa menyisipkan/mengubah role miliknya sendiri
     * hanya dengan menambahkan field "role" pada form/request.
     */
    private const ROLES = ['admin', 'dosen', 'student'];

    /**
     * Menampilkan daftar seluruh pengguna, dengan pencarian sederhana.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Menampilkan form tambah pengguna baru.
     */
    public function create(): View
    {
        return view('users.create', [
            'roles' => self::ROLES,
        ]);
    }

    /**
     * Menyimpan pengguna baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        // Pisahkan role dari data yang divalidasi supaya TIDAK ikut
        // di-mass-assign melalui User::create()/fill(). Role hanya boleh
        // masuk lewat penugasan eksplisit ($user->role = ...) di bawah.
        $role = $validated['role'];
        unset($validated['role']);

        $validated['password'] = Hash::make($validated['password']);

        // Mass assignment di sini hanya mengisi kolom yang memang ada
        // di $fillable (name, email, password). role TIDAK disertakan.
        $user = User::create($validated);

        // Penugasan eksplisit untuk role, sesuai ketentuan bahwa role
        // tidak boleh diisi lewat $fillable.
        $user->role = $role;
        $user->save();

        return redirect()
            ->route('pengguna.index')
            ->with('status', "Pengguna \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Menampilkan detail satu pengguna.
     */
    public function show(User $pengguna): View
    {
        return view('users.show', [
            'user' => $pengguna,
        ]);
    }

    /**
     * Menampilkan form edit pengguna.
     */
    public function edit(User $pengguna): View
    {
        return view('users.edit', [
            'user' => $pengguna,
            'roles' => self::ROLES,
        ]);
    }

    /**
     * Memperbarui data pengguna yang sudah ada.
     */
    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $validated = $this->validateUser($request, $pengguna->id);

        // Sama seperti store(): role dipisah agar tidak ikut mass
        // assignment lewat fill().
        $role = $validated['role'];
        unset($validated['role']);

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Password kosong pada form edit berarti "tidak diganti".
            unset($validated['password']);
        }

        // fill() di sini hanya menyentuh kolom $fillable (name, email,
        // dan password jika diisi). role tetap tidak disertakan.
        $pengguna->fill($validated);

        // Penugasan eksplisit untuk role.
        $pengguna->role = $role;
        $pengguna->save();

        return redirect()
            ->route('pengguna.index')
            ->with('status', "Pengguna \"{$pengguna->name}\" berhasil diperbarui.");
    }

    /**
     * Menghapus pengguna dari database.
     */
        public function destroy(User $pengguna): RedirectResponse
    {
        $name = $pengguna->name;

        try {
            $pengguna->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('pengguna.index')
                ->with('error', "Pengguna \"{$name}\" tidak bisa dihapus karena masih memiliki data terkait (misalnya tugas yang sudah dikumpulkan atau mata kuliah yang diampu). Hapus/pindahkan data tersebut terlebih dahulu.");
        }

        return redirect()
            ->route('pengguna.index')
            ->with('status', "Pengguna \"{$name}\" berhasil dihapus.");
    }

    /**
     * Validasi input form pengguna, dipakai bersama oleh store() dan update().
     */
    private function validateUser(Request $request, ?int $ignoreUserId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
        ]);
    }
}