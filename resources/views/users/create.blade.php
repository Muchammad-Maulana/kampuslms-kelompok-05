<x-layout title="Tambah Pengguna">

    {{-- Breadcrumb untuk navigasi --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">
                Dashboard
            </a>
            <span class="text-slate-400">/</span>
            <a href="{{ route('pengguna.index') }}" class="text-blue-600 hover:underline">
                Pengguna
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-600">Tambah</span>
        </div>
    </div>

    <div class="mb-8">
        <p class="text-sm font-semibold text-blue-600 mb-2">
            ADMINISTRASI / PENGGUNA
        </p>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Tambah Pengguna Baru
        </h2>

        <p class="text-sm text-slate-500 mt-2">
            Buat akun baru untuk admin, dosen, atau mahasiswa.
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 max-w-2xl">

        {{-- Menampilkan pesan validasi jika ada input yang tidak valid. --}}
        @if ($errors->any())
            <div class="mb-6 px-5 py-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                <p class="font-semibold mb-2">Terdapat kesalahan pada input:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pengguna.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block mb-1.5 text-sm font-semibold text-slate-700">
                    Nama Lengkap
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Nama pengguna"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block mb-1.5 text-sm font-semibold text-slate-700">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="nama@kampuslms.test"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block mb-1.5 text-sm font-semibold text-slate-700">
                    Kata Sandi
                </label>
                <input
                    type="password"
                    name="password"
                    placeholder="Minimal 8 karakter"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block mb-1.5 text-sm font-semibold text-slate-700">
                    Role
                </label>
                <select
                    name="role"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                    <option value="" disabled {{ old('role') ? '' : 'selected' }}>
                        Pilih role pengguna
                    </option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1.5">
                    Role akan diproses secara eksplisit oleh sistem, bukan lewat mass assignment.
                </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                
                 <a href="{{ route('pengguna.index') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 transition"
                >
                    Simpan Pengguna
                </button>
            </div>

        </form>

    </div>

</x-layout>