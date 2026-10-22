<x-layout title="Kelola Mahasiswa - {{ $course->name }}">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route(auth()->user()->role . '.mata-kuliah.show', $course) }}" class="text-sm font-semibold text-blue-700">Kembali ke mata kuliah</a>
        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-blue-700">{{ $course->code }}</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Kelola Pendaftaran</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $course->name }}</p>
            </div>
            <span class="text-sm text-slate-500">{{ count($enrolledStudentIds) }} mahasiswa terdaftar</span>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Pilihan mahasiswa tidak valid. Muat ulang dan coba kembali.
            </div>
        @endif

        <form action="{{ route(auth()->user()->role . '.mata-kuliah.enrollment.update', $course) }}" method="POST" class="mt-6">
            @csrf
            @method('PUT')
            <div class="divide-y divide-slate-200 border-y border-slate-200">
                @forelse ($students as $student)
                    <label class="flex cursor-pointer items-center gap-4 py-4">
                        <input
                            type="checkbox"
                            name="student_ids[]"
                            value="{{ $student->id }}"
                            @checked(in_array($student->id, old('student_ids', $enrolledStudentIds)))
                            class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-700"
                        >
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900">{{ $student->name }}</span>
                            <span class="block text-sm text-slate-500">{{ $student->email }}@if ($student->nim_nip) · {{ $student->nim_nip }}@endif</span>
                        </span>
                    </label>
                @empty
                    <p class="py-8 text-sm text-slate-500">Belum ada akun mahasiswa.</p>
                @endforelse
            </div>
            <div class="mt-5 flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500">Menyimpan pilihan akan mengganti daftar pendaftaran course ini.</p>
                <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Simpan pendaftaran</button>
            </div>
        </form>
    </div>
</x-layout>