<x-layout title="Tugas {{ $course->name }}">
    <section class="mb-7 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-800 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.mata-kuliah.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.mata-kuliah.show' : 'admin.mata-kuliah.show'), $course) }}" class="text-sm font-medium text-blue-200 hover:text-white">← {{ $course->code }} · Kembali ke mata kuliah</a>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-blue-200">{{ $course->name }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Daftar Tugas</h1>
                <p class="mt-2 max-w-2xl text-sm text-blue-100">Pantau tenggat, status pengumpulan, dan hasil tugas dalam satu tempat.</p>
            </div>
            @can('create', [\App\Models\Assignment::class, $course])
                <a href="{{ route(auth()->user()->role . '.mata-kuliah.assignments.create', $course) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-blue-900 shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-900">
                    <span class="mr-2 text-lg leading-none">+</span> Tambah tugas
                </a>
            @endcan
        </div>
        <div class="mt-7 flex flex-wrap gap-3 text-xs font-semibold text-blue-100">
            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">{{ $assignments->total() }} tugas</span>
            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">{{ ucfirst($course->status) }}</span>
        </div>
    </section>

    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Pencarian dan filter tugas">
        <label for="assignment-search" class="mb-2 block text-sm font-semibold text-slate-700">Cari tugas</label>
        <input id="assignment-search" data-assignment-search type="search" placeholder="Cari judul tugas atau instruksi..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
        <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Filter daftar tugas">
            <button type="button" data-assignment-filter="all" aria-pressed="true" class="rounded-full bg-blue-700 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5">Semua</button>
            @if (auth()->user()->role === 'mahasiswa')
                <button type="button" data-assignment-filter="not-submitted" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Belum dikumpulkan</button>
                <button type="button" data-assignment-filter="submitted" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Sudah dikumpulkan</button>
                <button type="button" data-assignment-filter="late" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Terlambat</button>
            @else
                <button type="button" data-assignment-filter="draft" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Draft</button>
                <button type="button" data-assignment-filter="overdue" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Deadline lewat</button>
                <button type="button" data-assignment-filter="upcoming" aria-pressed="false" class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">Akan datang</button>
            @endif
        </div>
    </section>

    <div data-assignment-list class="grid gap-4 lg:grid-cols-2">
        @forelse ($assignments as $assignment)
            @can('view', $assignment)
                @php
                    $mySubmission = auth()->user()->role === 'mahasiswa' ? $assignment->submissions->first() : null;
                    $isOverdue = $assignment->due_at->isPast();
                    $filterTokens = [];
                    if (auth()->user()->role === 'mahasiswa') {
                        $filterTokens[] = $mySubmission ? 'submitted' : 'not-submitted';
                        if ($mySubmission?->is_late || (! $mySubmission && $isOverdue)) {
                            $filterTokens[] = 'late';
                        }
                    } else {
                        $filterTokens[] = $assignment->status === 'draft' ? 'draft' : ($isOverdue ? 'overdue' : 'upcoming');
                    }
                @endphp
                <article
                    data-assignment-card
                    data-filter-tokens="{{ implode(' ', $filterTokens) }}"
                    data-search="{{ strtolower($assignment->title . ' ' . $assignment->instructions . ' ' . $assignment->status) }}"
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg"
                >
                    <a href="{{ route(request()->routeIs('dosen.*') ? 'dosen.assignments.show' : (request()->routeIs('mahasiswa.*') ? 'mahasiswa.assignments.show' : 'admin.assignments.show'), $assignment) }}" class="block p-5 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-300">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $assignment->status === 'published' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $assignment->status === 'published' ? 'Terbit' : 'Draft' }}</span>
                                <h2 class="mt-3 text-lg font-bold text-slate-900 transition group-hover:text-blue-800">{{ $assignment->title }}</h2>
                            </div>
                            <span aria-hidden="true" class="mt-1 text-xl font-bold text-slate-300 transition group-hover:translate-x-1 group-hover:text-blue-600">→</span>
                        </div>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $assignment->instructions }}</p>
                        <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                            @if (auth()->user()->role === 'mahasiswa')
                                @if ($mySubmission)
                                    <span class="rounded-full px-3 py-1.5 font-bold {{ $mySubmission->is_late ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $mySubmission->is_late ? 'Terlambat' : 'Tepat waktu' }}</span>
                                @elseif ($isOverdue)
                                    <span class="rounded-full bg-red-100 px-3 py-1.5 font-bold text-red-700">Belum dikumpulkan · deadline lewat</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-3 py-1.5 font-bold text-amber-700">Belum dikumpulkan</span>
                                @endif
                            @else
                                <span class="rounded-full px-3 py-1.5 font-bold {{ $isOverdue ? 'bg-red-100 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $isOverdue ? 'Deadline lewat' : 'Akan datang' }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 font-semibold text-slate-600">{{ $assignment->submissions_count }} pengumpulan</span>
                            @endif
                            <span class="font-medium text-slate-500">Batas: {{ $assignment->due_at->format('d M Y · H:i') }}</span>
                        </div>
                    </a>
                    @if (auth()->user()->role !== 'mahasiswa' && (auth()->user()->can('update', $assignment) || auth()->user()->can('delete', $assignment)))
                        <div class="flex gap-4 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                            @can('update', $assignment)
                                <a href="{{ route(auth()->user()->role . '.assignments.edit', $assignment) }}" class="text-xs font-bold text-blue-700 hover:text-blue-900">Edit tugas</a>
                            @endcan
                            @can('delete', $assignment)
                                <form action="{{ route(auth()->user()->role . '.assignments.destroy', $assignment) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tugas ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-red-700 hover:text-red-900">Hapus</button>
                                </form>
                            @endcan
                        </div>
                    @endif
                </article>
            @endcan
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center lg:col-span-2">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-700">✦</div>
                <h2 class="mt-4 font-bold text-slate-900">Belum ada tugas</h2>
                <p class="mt-1 text-sm text-slate-500">Tugas yang tersedia untuk mata kuliah ini akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    <p data-assignment-empty hidden class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Tidak ada tugas yang cocok dengan pencarian atau filter ini.</p>
    <div class="mt-5">{{ $assignments->links() }}</div>
</x-layout>
