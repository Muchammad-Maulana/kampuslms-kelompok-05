<x-layout title="403 - Akses Ditolak">
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <section class="w-full max-w-xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">
                Akses halaman
            </p>

            <p class="mt-4 text-8xl font-extrabold leading-none text-slate-900">
                403
            </p>

            <h1 class="mt-5 text-2xl font-bold text-slate-900">
                Akses ditolak
            </h1>

            <p class="mx-auto mt-3 max-w-md text-slate-600">
                Akun Anda tidak memiliki izin untuk membuka halaman ini. Jika Anda merasa seharusnya memiliki akses, hubungi administrator.
            </p>

            <a
                href="{{ route('dashboard') }}"
                class="mt-8 inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Kembali ke dashboard
            </a>
        </section>
    </div>
</x-layout>