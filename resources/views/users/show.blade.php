<x-layout title="Detail Pengguna">

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
            <span class="text-slate-600">{{ $user->name }}</span>
        </div>
    </div>

    {{-- Header dengan informasi utama --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-2xl">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-blue-600 mb-1">
                        DETAIL PENGGUNA
                    </p>
                    <h1 class="text-3xl font-bold text-slate-900">
                        {{ $user->name }}
                    </h1>
                    <p class="text-slate-600 mt-1">
                        {{ $user->email }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                
                 <a href="{{ route('pengguna.edit', $user) }}"
                    class="inline-flex items-center px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition"
                >
                    <span class="material-symbols-outlined mr-2">edit</span>
                    Ubah
                </a>
                
                 <a href="{{ route('pengguna.index') }}"
                    class="inline-flex items-center px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300 transition"
                >
                    <span class="material-symbols-outlined mr-2">arrow_back</span>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Grid informasi pengguna --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">
                Role
            </p>
            <p class="text-2xl font-bold text-blue-600 mt-3">
                {{ ucfirst($user->role) }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">
                Terdaftar Sejak
            </p>
            <p class="text-2xl font-bold text-slate-900 mt-3">
                {{ $user->created_at?->translatedFormat('d M Y') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">
                Terakhir Diperbarui
            </p>
            <p class="text-2xl font-bold text-slate-900 mt-3">
                {{ $user->updated_at?->translatedFormat('d M Y') }}
            </p>
        </div>

    </div>

    {{-- Aksi hapus --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-2">
            Zona Berbahaya
        </h2>
        <p class="text-sm text-slate-500 mb-4">
            Menghapus pengguna ini akan menghapus akunnya secara permanen dari sistem.
        </p>

        <form
            action="{{ route('pengguna.destroy', $user) }}"
            method="POST"
            onsubmit="return confirm('Hapus pengguna \'{{ $user->name }}\'? Tindakan ini tidak dapat dibatalkan.');"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="inline-flex items-center px-5 py-2.5 rounded-xl bg-red-50 text-red-600 font-semibold text-sm hover:bg-red-100 transition"
            >
                <span class="material-symbols-outlined mr-2">delete</span>
                Hapus Pengguna
            </button>
        </form>
    </div>

</x-layout>