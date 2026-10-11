<x-layout title="Tugas {{ $course->name }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $course->code }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Daftar Tugas</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.show' : 'admin.mata-kuliah.show'), $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke mata kuliah</a>
        @can('create', [\App\Models\Assignment::class, $course])
            <a href="{{ route(auth()->user()->role . '.mata-kuliah.assignments.create', $course) }}" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Tambah tugas</a>
        @endcan
    </div>
    <div class="divide-y divide-slate-200 border-y border-slate-200">
        @forelse ($assignments as $assignment)
            @can('view', $assignment)
                <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.assignments.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.assignments.show' : 'admin.assignments.show'), $assignment) }}" class="block py-4 hover:bg-slate-50">
                    <h2 class="font-semibold text-slate-900">{{ $assignment->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Batas {{ $assignment->due_at->format('d M Y H:i') }} · {{ ucfirst($assignment->status) }}</p>
                </a>
                @can('update', $assignment)
                    <a href="{{ route(auth()->user()->role . '.assignments.edit', $assignment) }}" class="text-sm font-semibold text-blue-700">Edit</a>
                @endcan
                @can('delete', $assignment)
                    <form action="{{ route(auth()->user()->role . '.assignments.destroy', $assignment) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ml-3 text-sm font-semibold text-red-700">Hapus</button>
                    </form>
                @endcan
            @endcan
        @empty
            <p class="py-8 text-sm text-slate-500">Belum ada tugas yang dapat ditampilkan.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $assignments->links() }}</div>
</x-layout>
