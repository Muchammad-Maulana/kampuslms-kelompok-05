<x-layout title="Materi {{ $course->name }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $course->code }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Materi Pembelajaran</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.show' : 'admin.mata-kuliah.show'), $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke mata kuliah</a>
        @can('create', [\App\Models\Material::class, $course])
            <a href="{{ route(auth()->user()->role . '.mata-kuliah.materials.create', $course) }}" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Tambah materi</a>
        @endcan
    </div>
    <div class="divide-y divide-slate-200 border-y border-slate-200">
        @forelse ($materials as $material)
            @can('view', $material)
                <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.materials.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.materials.show' : 'admin.materials.show'), $material) }}" class="block py-4 hover:bg-slate-50">
                    <h2 class="font-semibold text-slate-900">{{ $material->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $material->description ?: ucfirst($material->type) }} · {{ $material->uploader->name }}</p>
                </a>
                @can('update', $material)
                    <a href="{{ route(auth()->user()->role . '.materials.edit', $material) }}" class="text-sm font-semibold text-blue-700">Edit</a>
                @endcan
                @can('delete', $material)
                    <form action="{{ route(auth()->user()->role . '.materials.destroy', $material) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ml-3 text-sm font-semibold text-red-700">Hapus</button>
                    </form>
                @endcan
            @endcan
        @empty
            <p class="py-8 text-sm text-slate-500">Belum ada materi.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $materials->links() }}</div>
</x-layout>
