<x-layout title="Pengguna">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-blue-600">ADMINISTRASI / PENGGUNA</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Daftar Pengguna</h1>
            <p class="mt-2 text-sm text-slate-500">Kelola akun admin, dosen, dan mahasiswa.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Tambah Pengguna</a>
    </div>

    @if (session('success'))<div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>@endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-56 flex-1">
            <label for="q" class="block text-sm font-semibold text-slate-700">Cari pengguna</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Nama, email, atau NIM/NIP" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
        </div>
        <div class="w-48">
            <label for="role" class="block text-sm font-semibold text-slate-700">Role</label>
            <select id="role" name="role" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                <option value="">Semua role</option>
                @foreach (['admin', 'dosen', 'mahasiswa'] as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50"><tr><th class="px-6 py-4 font-semibold text-slate-500">Nama</th><th class="px-6 py-4 font-semibold text-slate-500">Email</th><th class="px-6 py-4 font-semibold text-slate-500">Role</th><th class="px-6 py-4 text-right font-semibold text-slate-500">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50"><td class="px-6 py-4 font-semibold text-slate-900">{{ $user->name }}</td><td class="px-6 py-4 text-slate-600">{{ $user->email }}</td><td class="px-6 py-4 capitalize">{{ $user->role }}</td><td class="px-6 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.users.show', $user) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Detail</a><a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Edit</a><form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button></form></div></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $users->links() }}</div>
</x-layout>
