<x-layout title="{{ $assignment->title }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $assignment->course->code }} · TUGAS</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $assignment->title }}</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.assignments.index' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.assignments.index' : 'admin.mata-kuliah.assignments.index'), $assignment->course) }}" class="text-sm font-semibold text-blue-700">Kembali ke tugas</a>
    </div>

    @if (session('success') || session('error') || $errors->any())
        @php $hasSubmissionError = session('error') || $errors->any(); @endphp
        <div
            data-flash-message
            role="{{ $hasSubmissionError ? 'alert' : 'status' }}"
            aria-live="{{ $hasSubmissionError ? 'assertive' : 'polite' }}"
            class="fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-md items-start justify-between gap-4 rounded-xl border p-4 shadow-lg {{ $hasSubmissionError ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}"
        >
            <div class="text-sm">
                <p class="font-semibold">{{ session('success') ?? session('error') ?? 'Pengumpulan belum berhasil.' }}</p>
                @if ($errors->any())
                    <ul class="mt-1 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button type="button" data-dismiss-flash aria-label="Tutup notifikasi" class="shrink-0 rounded px-2 font-semibold hover:bg-black/5">&times;</button>
        </div>
    @endif

    <div class="max-w-3xl border-y border-slate-200 py-5">
        <p class="whitespace-pre-line text-slate-700">{{ $assignment->instructions }}</p>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Batas pengumpulan</dt><dd class="font-semibold">{{ $assignment->due_at->format('d M Y H:i') }}</dd></div>
            <div><dt class="text-slate-500">Nilai maksimal</dt><dd class="font-semibold">{{ $assignment->max_score }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-semibold capitalize">{{ $assignment->status }}</dd></div>
        </dl>
    </div>

    @auth
        @if (auth()->user()->role === 'mahasiswa')
            @php $submission = $studentSubmission; @endphp
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Pengumpulan tugas</h2>
                @if ($submission)
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold">File terkirim:</p>
                        <p class="mt-1">{{ $submission->original_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($submission->file_size / 1048576, 2) }} MB</p>
                        <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $submission->is_late ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $submission->is_late ? 'Terlambat dikumpulkan' : 'Dikumpulkan tepat waktu' }}
                        </span>
                        <p class="mt-1 text-xs text-slate-500">Dikirim {{ $submission->submitted_at?->format('d M Y H:i') ?? 'belum tercatat' }}</p>
                        <a class="mt-3 inline-flex rounded-md bg-blue-700 px-3 py-2 text-xs font-semibold text-white" href="{{ route('mahasiswa.submissions.download', $submission) }}">Unduh pengumpulan saya</a>
                    </div>
                    @if ($canSubmit)
                        <form action="{{ route('mahasiswa.mata-kuliah.assignments.submissions.update', [$assignment->course, $assignment, $submission]) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="file" class="mb-1 block text-sm font-semibold text-slate-700">Ganti file jawaban (opsional, PDF/DOC/DOCX; maksimal 5 MB)</label>
                                <input id="file" name="file" type="file" accept=".pdf,.doc,.docx" class="block w-full rounded-lg border border-slate-300 text-sm text-slate-700">
                            </div>
                            <div>
                                <label for="note" class="mb-1 block text-sm font-semibold text-slate-700">Catatan</label>
                                <textarea id="note" name="note" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $submission->note }}</textarea>
                            </div>
                            <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Perbarui pengumpulan</button>
                        </form>
                    @endif
                    @if (!$submission->grade)
                        <p class="mt-4 text-sm text-slate-500">Tugas sudah dikirim dan menunggu penilaian dari dosen.</p>
                    @else
                        <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-900">
                            <p class="font-semibold">Nilai: {{ $submission->grade->score }} / {{ $assignment->max_score }}</p>
                            <p class="mt-2 font-semibold">Feedback dosen</p>
                            <p class="mt-1 whitespace-pre-line">{{ $submission->grade->feedback ?: 'Tidak ada feedback.' }}</p>
                        </div>
                    @endif
                @elseif ($canSubmit)
                    <form action="{{ route('mahasiswa.mata-kuliah.assignments.submissions.store', [$assignment->course, $assignment]) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="file" class="mb-1 block text-sm font-semibold text-slate-700">File jawaban</label>
                            <input id="file" name="file" type="file" accept=".pdf,.doc,.docx" required class="block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                        </div>
                        <div>
                            <label for="note" class="mb-1 block text-sm font-semibold text-slate-700">Catatan</label>
                            <textarea id="note" name="note" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Opsional"></textarea>
                        </div>
                        <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Kirim tugas</button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-slate-500">Batas pengumpulan telah lewat dan tugas terlambat tidak diizinkan.</p>
                @endif
            </div>
        @else
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Pengumpulan mahasiswa</h2>
                @forelse ($students as $student)
                    @php $submission = $submissions->get($student->id); @endphp
                    <div class="mt-4 rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                @if ($submission)
                                    <p class="text-sm text-slate-500">{{ $submission->original_name }} · {{ number_format($submission->file_size / 1048576, 2) }} MB · {{ $submission->submitted_at?->format('d M Y H:i') }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $submission->is_late ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $submission->is_late ? 'Terlambat dikumpulkan' : 'Dikumpulkan tepat waktu' }}
                                    </span>
                                @else
                                    <p class="text-sm text-slate-500">Belum mengumpulkan</p>
                                @endif
                            </div>
                            @if (!$submission)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Belum mengumpulkan</span>
                            @elseif ($submission->grade)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Nilai: {{ $submission->grade->score }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Belum dinilai</span>
                            @endif
                        </div>

                        @if ($submission)
                            <a class="mt-3 inline-flex rounded-md bg-blue-700 px-3 py-2 text-xs font-semibold text-white" href="{{ route(auth()->user()->role . '.submissions.download', $submission) }}">Unduh submission</a>
                        @endif

                        @if ($submission && $submission->note)
                            <p class="mt-3 text-sm text-slate-600">Catatan: {{ $submission->note }}</p>
                        @endif

                        @if ($submission && !$submission->grade)
                            <form action="{{ route(auth()->user()->role . '.mata-kuliah.assignments.submissions.grade', [$assignment->course, $assignment, $submission]) }}" method="POST" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="score-{{ $submission->id }}" class="block text-sm font-semibold text-slate-700">Nilai</label>
                                    <input id="score-{{ $submission->id }}" name="score" type="number" min="0" max="{{ $assignment->max_score }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label for="feedback-{{ $submission->id }}" class="block text-sm font-semibold text-slate-700">Feedback</label>
                                    <textarea id="feedback-{{ $submission->id }}" name="feedback" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Opsional"></textarea>
                                </div>
                                <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Simpan nilai</button>
                            </form>
                        @elseif ($submission)
                            <div class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                <p class="font-semibold">Feedback</p>
                                <p class="mt-1 whitespace-pre-line">{{ $submission->grade->feedback ?: 'Tidak ada feedback.' }}</p>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="mt-4 text-sm text-slate-500">Belum ada mahasiswa yang mengumpulkan tugas ini.</p>
                @endforelse
            </div>
        @endif
    @endauth
</x-layout>
