<x-layout title="Tambah Mata Kuliah">
    <div class="max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900">Tambah Mata Kuliah</h1>
        <p class="mt-2 text-sm text-slate-500">Masukkan informasi mata kuliah baru.</p>

        @include('courses.form', ['course' => null, 'formAction' => route(auth()->user()->role . '.mata-kuliah.store'), 'formMethod' => 'POST'])
    </div>
</x-layout>
