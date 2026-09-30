<x-layout title="Tambah Mata Kuliah">

    <div class="bg-white p-6 rounded shadow max-w-lg mx-auto">

        <h1 class="text-2xl font-bold mb-4">Tambah Mata Kuliah</h1>

        <form action="{{ route('mata-kuliah.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block mb-1 font-medium">Kode Mata Kuliah</label>
            <input type="text" name="code" class="w-full border rounded p-2"
               value="{{ old('code') }}" placeholder="SI2514027" required>
            @error('code')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-medium">Nama Mata Kuliah</label>
            <input type="text" name="name" class="w-full border rounded p-2"
                value="{{ old('name') }}" placeholder="Pemrograman Web Lanjut" required>
            @error('name')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-medium">Deskripsi</label>
            <textarea name="description" class="w-full border rounded p-2"
                    placeholder="Deskripsi mata kuliah">{{ old('description') }}</textarea>
            @error('description')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-medium">SKS</label>
            <input type="number" name="sks" class="w-full border rounded p-2"
                value="{{ old('sks') }}" placeholder="3" min="1" required>
            @error('sks')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-medium">Dosen Pengampu</label>
            <select name="lecturer_id" class="w-full border rounded p-2" required>
                <option value="">-- Pilih Dosen --</option>

                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" {{ old('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                        {{ $lecturer->name }}
                    </option>
                @endforeach
            </select>
            @error('lecturer_id')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-medium">Status</label>
            <select name="status" class="w-full border rounded p-2" required>
                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            @error('status')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('mata-kuliah.index') }}"
            class="px-4 py-2 border rounded">
                Batal
            </a>

            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded">
                Simpan
            </button>
        </div>
    </form>
    </div>

</x-layout>