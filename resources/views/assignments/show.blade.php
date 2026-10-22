<x-layout title="{{ $assignment->title }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $assignment->course->code }} · TUGAS</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $assignment->title }}</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.assignments.index' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.assignments.index' : 'admin.mata-kuliah.assignments.index'), $assignment->course) }}" class="text-sm font-semibold text-blue-700">Kembali ke tugas</a>
    </div>
    <div class="max-w-3xl border-y border-slate-200 py-5">
        <p class="whitespace-pre-line text-slate-700">{{ $assignment->instructions }}</p>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Batas pengumpulan</dt><dd class="font-semibold">{{ $assignment->due_at->format('d M Y H:i') }}</dd></div>
            <div><dt class="text-slate-500">Nilai maksimal</dt><dd class="font-semibold">{{ $assignment->max_score }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-semibold capitalize">{{ $assignment->status }}</dd></div>
        </dl>
    </div>
</x-layout>
