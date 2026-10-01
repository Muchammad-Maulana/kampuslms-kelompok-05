<x-layout title="403 - Akses Ditolak">
    <div class="min-h-[70vh] flex items-center justify-center">
        <div class="text-center">
            <div class="text-8xl font-bold text-red-600">403</div>

            <h1 class="mt-4 text-3xl font-bold text-slate-800">
                Akses ditolak
            </h1>

            <p class="mt-3 text-slate-500">
                Anda tidak memiliki izin untuk mengakses atau mengubah data ini.
            </p>

            <a
                href="{{ route('dosen.mata-kuliah.index') }}"
                class="mt-8 inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700"
            >
                Kembali ke Mata Kuliah
            </a>
        </div>
    </div>
</x-layout>