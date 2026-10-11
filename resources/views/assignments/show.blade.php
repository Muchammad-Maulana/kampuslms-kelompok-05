<x-layout title="{{ $assignment->title }}">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-700">{{ $assignment->course->code }} · TUGAS</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $assignment->title }}</h1>
        </div>
        <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.assignments.index' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.assignments.index' : 'admin.mata-kuliah.assignments.index'), $assignment->course) }}" class="text-sm font-semibold text-blue-700">Kembali ke tugas</a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
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
            @php $submission = $assignment->submissions()->where('user_id', auth()->id())->first(); @endphp
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Pengumpulan tugas</h2>
                @if ($submission)
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold">File terkirim:</p>
                        <p class="mt-1">{{ $submission->original_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">Dikirim {{ $submission->submitted_at?->format('d M Y H:i') ?? 'belum tercatat' }}</p>
                    </div>
                    @if (!$submission->grade)
                        <p class="mt-4 text-sm text-slate-500">Tugas sudah dikirim dan menunggu penilaian dari dosen.</p>
                    @endif
                @else
                    <form action="{{ route('mahasiswa.mata-kuliah.assignments.submissions.store', [$assignment->course, $assignment]) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="file" class="mb-1 block text-sm font-semibold text-slate-700">File jawaban</label>
                            <input id="file" name="file" type="file" required class="block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                        </div>
                        <div>
                            <label for="note" class="mb-1 block text-sm font-semibold text-slate-700">Catatan</label>
                            <textarea id="note" name="note" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Opsional"></textarea>
                        </div>
                        <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Kirim tugas</button>
                    </form>
                @endif
            </div>
        @else
            @php $submissions = $assignment->submissions()->with(['user', 'grade'])->get(); @endphp
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Pengumpulan mahasiswa</h2>
                @forelse ($submissions as $submission)
                    <div class="mt-4 rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $submission->user->name }}</p>
                                <p class="text-sm text-slate-500">{{ $submission->original_name }} · {{ $submission->submitted_at?->format('d M Y H:i') }}</p>
                            </div>
                            @if ($submission->grade)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Nilai: {{ $submission->grade->score }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Belum dinilai</span>
                            @endif
                        </div>

                        @if ($submission->note)
                            <p class="mt-3 text-sm text-slate-600">Catatan: {{ $submission->note }}</p>
                        @endif

                        @if (!$submission->grade)
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
                        @else
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
