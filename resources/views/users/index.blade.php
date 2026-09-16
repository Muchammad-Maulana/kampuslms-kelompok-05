<x-layout title="Pengguna">

    {{-- Notifikasi status hasil aksi (tambah/edit/hapus). --}}
    @if (session('status'))
        <div class="mb-6 px-5 py-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header halaman memberikan informasi utama tentang daftar pengguna. --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div>
                <p class="text-sm font-semibold text-blue-600 mb-2">
                    ADMINISTRASI / PENGGUNA
                </p>

                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Manajemen Pengguna
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Kelola akun admin, dosen, dan mahasiswa pada sistem.
                </p>
            </div>

            
             <a href="{{ route('pengguna.create') }}"
                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 transition"
            >
                <span class="material-symbols-outlined text-base">add</span>
                Tambah Pengguna
            </a>

        </div>
    </div>

    {{-- Ringkasan sederhana jumlah pengguna. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Pengguna</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $users->total() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Halaman</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">
                {{ $users->currentPage() }} / {{ $users->lastPage() }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pencarian Aktif</p>
            <p class="text-lg font-bold text-blue-600 mt-1 truncate">
                {{ $search !== '' ? $search : '—' }}
            </p>
        </div>

    </div>

    {{-- Tabel digunakan untuk menampilkan data pengguna secara terstruktur. --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">
                    Daftar Pengguna
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Seluruh akun yang terdaftar pada sistem.
                </p>
            </div>

            {{-- Form pencarian sederhana berdasarkan nama atau email. --}}
            <form action="{{ route('pengguna.index') }}" method="GET" class="flex items-center gap-2">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama atau email..."
                    class="w-full md:w-64 px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button
                    type="submit"
                    class="px-4 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-semibold hover:bg-slate-900 transition"
                >
                    Cari
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Nama
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Email
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Role
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-500">
                            Terdaftar
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-500">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($users as $user)

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-xs">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <p class="font-semibold text-slate-900">
                                        {{ $user->name }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-slate-700">
                                    {{ $user->email }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">
                                @php
                                    $roleStyles = [
                                        'admin' => 'bg-purple-50 text-purple-700',
                                        'dosen' => 'bg-amber-50 text-amber-700',
                                        'student' => 'bg-blue-50 text-blue-700',
                                    ];
                                    $roleLabels = [
                                        'admin' => 'Admin',
                                        'dosen' => 'Dosen',
                                        'student' => 'Mahasiswa',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 rounded-lg font-semibold text-xs {{ $roleStyles[$user->role] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-slate-600">
                                    {{ $user->created_at?->translatedFormat('d M Y') }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center justify-center gap-2">

                                    
                                     <a href="{{ route('pengguna.show', $user) }}"
                                        class="inline-flex items-center px-3 py-2 rounded-lg bg-slate-100 text-slate-700 font-semibold text-xs hover:bg-slate-200 transition"
                                    >
                                        Detail
                                    </a>

                                    
                                     <a href="{{ route('pengguna.edit', $user) }}"
                                        class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold text-xs hover:bg-blue-700 transition"
                                    >
                                        Ubah
                                    </a>

                                    <form
                                        action="{{ route('pengguna.destroy', $user) }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus pengguna \'{{ $user->name }}\'? Tindakan ini tidak dapat dibatalkan.');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex items-center px-3 py-2 rounded-lg bg-red-50 text-red-600 font-semibold text-xs hover:bg-red-100 transition"
                                        >
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                Belum ada pengguna yang cocok dengan pencarian.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($users->hasPages())
            <div class="px-6 py-5 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif

    </div>

</x-layout>