<x-layout title="Tugas {{ $course->name }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $course->code }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Daftar Tugas</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.show' : 'admin.mata-kuliah.show'), $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke mata kuliah</a>
    </div>
    <div class="divide-y divide-slate-200 border-y border-slate-200">
        @forelse ($assignments as $assignment)
            <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.assignments.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.assignments.show' : 'admin.assignments.show'), $assignment) }}" class="block py-4 hover:bg-slate-50">
                <h2 class="font-semibold text-slate-900">{{ $assignment->title }}</h2>
                <p class="mt-1 text-sm text-slate-600">Batas {{ $assignment->due_at->format('d M Y H:i') }} · {{ ucfirst($assignment->status) }}</p>
            </a>
        @empty
            <p class="py-8 text-sm text-slate-500">Belum ada tugas yang dapat ditampilkan.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $assignments->links() }}</div>
</x-layout>
