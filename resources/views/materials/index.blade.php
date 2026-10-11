<x-layout title="Materi {{ $course->name }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $course->code }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Materi Pembelajaran</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.show' : 'admin.mata-kuliah.show'), $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke mata kuliah</a>
    </div>
    <div class="divide-y divide-slate-200 border-y border-slate-200">
        @forelse ($materials as $material)
            @can('view', $material)
                <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.materials.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.materials.show' : 'admin.materials.show'), $material) }}" class="block py-4 hover:bg-slate-50">
                    <h2 class="font-semibold text-slate-900">{{ $material->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $material->description ?: ucfirst($material->type) }} · {{ $material->uploader->name }}</p>
                </a>
            @endcan
        @empty
            <p class="py-8 text-sm text-slate-500">Belum ada materi.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $materials->links() }}</div>
</x-layout>
