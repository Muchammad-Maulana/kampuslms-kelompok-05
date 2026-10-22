@php
    $editing = $material->exists;
    $rolePrefix = auth()->user()->role;
    $action = $editing
        ? route($rolePrefix . '.materials.update', $material)
        : route($rolePrefix . '.mata-kuliah.materials.store', $course);
@endphp

<x-layout :title="$editing ? 'Edit Materi' : 'Tambah Materi'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route($rolePrefix . '.mata-kuliah.materials.index', $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke materi</a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $editing ? 'Edit Materi' : 'Tambah Materi' }}</h1>
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div>
                <label for="title" class="block text-sm font-semibold text-slate-700">Judul</label>
                <input id="title" name="title" value="{{ old('title', $material->title) }}" required maxlength="255" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-semibold text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('description', $material->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="type" class="block text-sm font-semibold text-slate-700">Jenis</label>
                <select id="type" name="type" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="link" @selected(old('type', $material->type) === 'link')>Tautan</option>
                    <option value="file" @selected(old('type', $material->type) === 'file')>Berkas PDF, PPTX, atau DOCX</option>
                </select>
                @error('type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="external_url" class="block text-sm font-semibold text-slate-700">URL materi (untuk jenis tautan)</label>
                <input id="external_url" name="external_url" type="url" value="{{ old('external_url', $material->external_url) }}" maxlength="2048" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                @error('external_url')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="file" class="block text-sm font-semibold text-slate-700">Berkas materi (maksimal 20 MB)</label>
                <input id="file" name="file" type="file" accept=".pdf,.pptx,.docx" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                @error('file')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">{{ $editing ? 'Simpan perubahan' : 'Simpan materi' }}</button>
        </form>
    </div>
</x-layout>