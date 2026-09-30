<x-layout title="Edit Mata Kuliah">
    <div class="max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900">Edit Mata Kuliah</h1>
        <p class="mt-2 text-sm text-slate-500">Perbarui informasi mata kuliah.</p>

        @include('courses.form', ['course' => $course, 'formAction' => route('admin.mata-kuliah.update', $course), 'formMethod' => 'PUT'])
    </div>
</x-layout>
