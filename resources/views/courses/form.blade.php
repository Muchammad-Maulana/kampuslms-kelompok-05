<form action="{{ $formAction }}" method="POST" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label for="code" class="block text-sm font-semibold text-slate-700">Kode</label>
        <input id="code" name="code" value="{{ old('code', $course?->code) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700">Nama</label>
        <input id="name" name="name" value="{{ old('name', $course?->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-slate-700">Deskripsi</label>
        <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $course?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="sks" class="block text-sm font-semibold text-slate-700">SKS</label>
            <input id="sks" type="number" name="sks" min="1" max="6" value="{{ old('sks', $course?->sks) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div class="sm:col-span-2">
            <label for="lecturer_id" class="block text-sm font-semibold text-slate-700">Dosen</label>
            <select id="lecturer_id" name="lecturer_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Pilih dosen</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $course?->lecturer_id) == $lecturer->id)>{{ $lecturer->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
        <select id="status" name="status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach (['draft', 'active', 'archived'] as $status)
                <option value="{{ $status }}" @selected(old('status', $course?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-3">
        <a href="{{ route(auth()->user()->role . '.mata-kuliah.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</a>
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
    </div>
</form>
