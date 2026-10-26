<x-layout title="Detail Mata Kuliah">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-blue-600">DETAIL MATA KULIAH</p><h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $course->name }}</h1><p class="mt-2 text-sm text-slate-500">{{ $course->code }} · {{ $course->sks }} SKS</p></div><div class="flex gap-2">@can('manageEnrollment', $course)<a href="{{ route($routePrefix . '.enrollment.edit', $course) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Kelola mahasiswa</a>@endcan @can('update', $course)<a href="{{ route('admin.mata-kuliah.edit', $course) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white">Edit</a>@endcan<a href="{{ route($routePrefix . '.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kembali</a></div></div>
    @if (session('success'))<div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Informasi Course</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-slate-500">Dosen Pengampu</dt><dd class="font-semibold text-slate-900">{{ $course->lecturer?->name ?? 'Belum ditentukan' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-semibold capitalize text-slate-900">{{ $course->status }}</dd></div>
                <div><dt class="text-slate-500">Deskripsi</dt><dd class="text-slate-700">{{ $course->description ?: 'Tidak ada deskripsi.' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Materi dan Tugas</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                <a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" href="{{ route(str_replace('mata-kuliah', 'mata-kuliah.materials', $routePrefix) . '.index', $course) }}">Semua Materi ({{ $course->materials->count() }})</a>
                <a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" href="{{ route(str_replace('mata-kuliah', 'mata-kuliah.assignments', $routePrefix) . '.index', $course) }}">Semua Tugas ({{ $course->assignments->count() }})</a>
            </div>
            <div class="mt-5">
                <h3 class="font-semibold text-slate-900">Materi tersedia</h3>
                @forelse ($course->materials as $material)
                    <a class="block border-b border-slate-100 py-3 last:border-0 hover:text-blue-700" href="{{ route(str_replace('mata-kuliah', 'mata-kuliah.materials', $routePrefix) . '.scoped-show', [$course, $material]) }}">
                        <span class="font-medium">{{ $material->title }}</span>
                        <span class="ml-2 text-sm text-slate-500">{{ $material->original_name ?: ucfirst($material->type) }}</span>
                    </a>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Belum ada materi yang diunggah.</p>
                @endforelse
            </div>
            <div class="mt-5">
                <h3 class="font-semibold text-slate-900">Tugas</h3>
                @forelse ($course->assignments as $assignment)
                    <div class="border-b border-slate-100 py-3 last:border-0">
                        <p class="font-semibold text-slate-900">{{ $assignment->title }}</p>
                        <p class="text-sm text-slate-500">Batas: {{ $assignment->due_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Belum ada tugas.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
