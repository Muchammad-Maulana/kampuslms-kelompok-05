<x-layout title="Rekap Nilai Saya">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Akademik</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Rekap nilai saya</h1>
            <p class="mt-1 text-sm text-slate-600">Daftar ini hanya memuat nilai dari submission Anda sendiri.</p>
        </div>

        <div class="space-y-3">
            @forelse ($grades as $grade)
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-blue-700">{{ $grade->submission->assignment->course->name }}</p>
                            <h2 class="mt-1 font-bold text-slate-900">{{ $grade->submission->assignment->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Dinilai {{ $grade->graded_at?->format('d M Y H:i') }}</p>
                        </div>
                        <p class="rounded-lg bg-green-50 px-4 py-2 text-lg font-bold text-green-800">{{ $grade->score }} <span class="text-sm font-medium">/ {{ $grade->submission->assignment->max_score }}</span></p>
                    </div>
                    @if ($grade->feedback)
                        <div class="mt-4 rounded-lg bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">Feedback dosen</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $grade->feedback }}</p>
                        </div>
                    @endif
                    <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline" href="{{ route('mahasiswa.mata-kuliah.assignments.scoped-show', [$grade->submission->assignment->course, $grade->submission->assignment]) }}">Lihat tugas</a>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <p class="font-semibold text-slate-800">Belum ada nilai</p>
                    <p class="mt-1 text-sm text-slate-500">Nilai tugas yang sudah diberikan dosen akan tampil di sini.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $grades->links() }}</div>
    </div>
</x-layout>
