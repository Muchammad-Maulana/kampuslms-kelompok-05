<x-layout title="Edit Mata Kuliah">

    <div class="bg-white p-6 rounded shadow max-w-lg mx-auto">

        <h1 class="text-2xl font-bold mb-6">Edit Mata Kuliah</h1>

        <form action="{{ route('mata-kuliah.update', $course->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block mb-1 font-medium">Kode Mata Kuliah</label>
                <input
                    type="text"
                    name="code"
                    value="{{ old('code', $course->code) }}"
                    class="w-full border rounded p-2"
                    required
                >
                @error('code')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-1 font-medium">Nama Mata Kuliah</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $course->name) }}"
                    class="w-full border rounded p-2"
                    required
                >
                @error('name')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-1 font-medium">Deskripsi</label>
                <textarea
                    name="description"
                    class="w-full border rounded p-2"
                >{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-1 font-medium">SKS</label>
                <input
                    type="number"
                    name="sks"
                    value="{{ old('sks', $course->sks) }}"
                    class="w-full border rounded p-2"
                    min="1"
                    required
                >
                @error('sks')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-1 font-medium">Dosen Pengampu</label>
                <select name="lecturer_id" class="w-full border rounded p-2" required>
                    @foreach ($lecturers as $lecturer)
                        <option
                            value="{{ $lecturer->id }}"
                            {{ old('lecturer_id', $course->lecturer_id) == $lecturer->id ? 'selected' : '' }}
                        >
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
                    <option value="draft" {{ old('status', $course->status) === 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="active" {{ old('status', $course->status) === 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="archived" {{ old('status', $course->status) === 'archived' ? 'selected' : '' }}>
                        Archived
                    </option>
                </select>
                @error('status')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end space-x-2">
                <a
                    href="{{ route('mata-kuliah.index') }}"
                    class="px-4 py-2 border rounded"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

</x-layout>