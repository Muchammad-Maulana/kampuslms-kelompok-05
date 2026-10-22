@php
    $editing = $assignment->exists;
    $rolePrefix = auth()->user()->role;
    $action = $editing
        ? route($rolePrefix . '.assignments.update', $assignment)
        : route($rolePrefix . '.mata-kuliah.assignments.store', $course);
@endphp

<x-layout :title="$editing ? 'Edit Tugas' : 'Tambah Tugas'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route($rolePrefix . '.mata-kuliah.assignments.index', $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke tugas</a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $editing ? 'Edit Tugas' : 'Tambah Tugas' }}</h1>
        <form action="{{ $action }}" method="POST" class="mt-6 space-y-5">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div>
                <label for="title" class="block text-sm font-semibold text-slate-700">Judul</label>
                <input id="title" name="title" value="{{ old('title', $assignment->title) }}" required maxlength="255" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="instructions" class="block text-sm font-semibold text-slate-700">Instruksi</label>
                <textarea id="instructions" name="instructions" rows="5" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('instructions', $assignment->instructions) }}</textarea>
                @error('instructions')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="due_at" class="block text-sm font-semibold text-slate-700">Batas pengumpulan</label>
                    <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @error('due_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="max_score" class="block text-sm font-semibold text-slate-700">Nilai maksimal</label>
                    <input id="max_score" name="max_score" type="number" min="1" max="100" value="{{ old('max_score', $assignment->max_score ?? 100) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @error('max_score')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="allow_late" class="block text-sm font-semibold text-slate-700">Pengumpulan terlambat</label>
                    <select id="allow_late" name="allow_late" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="1" @selected((string) old('allow_late', (int) ($assignment->allow_late ?? true)) === '1')>Diizinkan</option>
                        <option value="0" @selected((string) old('allow_late', (int) ($assignment->allow_late ?? true)) === '0')>Tidak diizinkan</option>
                    </select>
                    @error('allow_late')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="draft" @selected(old('status', $assignment->status ?? 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $assignment->status) === 'published')>Published</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">{{ $editing ? 'Simpan perubahan' : 'Simpan tugas' }}</button>
        </form>
    </div>
</x-layout>