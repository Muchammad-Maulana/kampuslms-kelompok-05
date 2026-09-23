<x-layout title="Mata Kuliah">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-blue-600">AKADEMIK / MATA KULIAH</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Daftar Mata Kuliah</h1>
            <p class="mt-2 text-sm text-slate-500">Data berikut diambil dari database KampusLMS.</p>
        </div>
        <a href="{{ route('mata-kuliah.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Tambah Mata Kuliah</a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('mata-kuliah.index') }}" class="mb-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1.5fr_220px_auto]">
        <div>
            <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari kode atau nama mata kuliah" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                <option value="">Semua status</option>
                @foreach (['draft', 'active', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
            <a href="{{ route('mata-kuliah.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset</a>
        </div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Mata Kuliah</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $courses->total() }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total SKS</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ App\Models\Course::sum('sks') }}</p></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50"><tr><th class="px-6 py-4 font-semibold text-slate-500">Kode</th><th class="px-6 py-4 font-semibold text-slate-500">Mata Kuliah</th><th class="px-6 py-4 font-semibold text-slate-500">SKS</th><th class="px-6 py-4 font-semibold text-slate-500">Dosen</th><th class="px-6 py-4 text-right font-semibold text-slate-500">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($courses as $course)
            <tr class="hover:bg-slate-50"><td class="px-6 py-4 font-semibold text-blue-700">{{ $course->code }}</td><td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $course->name }}</p><p class="mt-1 text-xs capitalize text-slate-400">{{ $course->status }}</p></td><td class="px-6 py-4">{{ $course->sks }}</td><td class="px-6 py-4">{{ $course->lecturer?->name ?? 'Belum ditentukan' }}</td><td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><a href="{{ route('mata-kuliah.show', $course) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Detail</a><a href="{{ route('mata-kuliah.edit', $course) }}" class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Edit</a><form action="{{ route('mata-kuliah.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini?')">@csrf @method('DELETE')<button type="submit" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button></form></div></td></tr>
        @empty
            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Belum ada mata kuliah.</td></tr>
        @endforelse
    </tbody></table></div></div>

    <div class="mt-6">
        {{ $courses->links() }}
    </div>
</x-layout>
