<x-layout title="Rekap Nilai — {{ $course->name }}">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">{{ $course->code }} · Rekap nilai</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $course->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Nilai dan status pengumpulan hanya ditampilkan untuk mata kuliah yang dapat Anda akses.</p>
        </div>

        @forelse ($assignments as $assignment)
            <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h2 class="font-bold text-slate-900">{{ $assignment->title }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Nilai maksimal: {{ $assignment->max_score }} · {{ $assignment->status === 'published' ? 'Terbit' : 'Draft' }}</p>
                </div>
                @php($submissionsByUser = $assignment->submissions->keyBy('user_id'))
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr><th class="px-5 py-3">Mahasiswa</th><th class="px-5 py-3">Pengumpulan</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Feedback</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($course->students as $student)
                                @php($submission = $submissionsByUser->get($student->id))
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $student->name }}</td>
                                    <td class="px-5 py-3 text-slate-600">
                                        @if ($submission)
                                            {{ $submission->submitted_at?->format('d M Y H:i') }}{{ $submission->is_late ? ' · Terlambat' : '' }}
                                        @else
                                            Belum mengumpulkan
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-slate-800">{{ $submission?->grade?->score ?? 'Belum dinilai' }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $submission?->grade?->feedback ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-5 text-center text-slate-500">Belum ada mahasiswa terdaftar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Belum ada tugas pada mata kuliah ini.</div>
        @endforelse
    </div>
</x-layout>
