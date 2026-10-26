<x-layout title="{{ $material->title }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $material->course->code }} · MATERI</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $material->title }}</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.materials.index' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.materials.index' : 'admin.mata-kuliah.materials.index'), $material->course) }}" class="text-sm font-semibold text-blue-700">Kembali ke materi</a>
    </div>
    <div class="max-w-3xl border-y border-slate-200 py-5">
        @if ($material->description)
            <p class="whitespace-pre-line text-slate-700">{{ $material->description }}</p>
        @endif
        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Jenis</dt><dd class="font-semibold capitalize">{{ $material->type }}</dd></div>
            <div><dt class="text-slate-500">Diunggah oleh</dt><dd class="font-semibold">{{ $material->uploader->name }}</dd></div>
            @if ($material->external_url)
                <div class="sm:col-span-2"><dt class="text-slate-500">Tautan</dt><dd><a class="break-all font-semibold text-blue-700 underline" href="{{ $material->external_url }}" rel="noopener noreferrer">{{ $material->external_url }}</a></dd></div>
            @endif
            @if ($material->original_name)
                <div><dt class="text-slate-500">Nama berkas</dt><dd class="font-semibold">{{ $material->original_name }}</dd></div>
                <div><dt class="text-slate-500">Ukuran berkas</dt><dd class="font-semibold">{{ number_format($material->file_size / 1048576, 2) }} MB</dd></div>
                <div class="sm:col-span-2"><a class="inline-flex rounded-md bg-blue-700 px-4 py-2 font-semibold text-white" href="{{ route(auth()->user()->role . '.materials.download', $material) }}">Unduh materi</a></div>
            @endif
        </dl>
    </div>
</x-layout>
