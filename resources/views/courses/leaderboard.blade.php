<x-layout title="Papan Peringkat">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-blue-600">AKADEMIK / {{ $course->code }}</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Papan Peringkat</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $course->name }} · Berdasarkan pengumpulan tugas tepat waktu.</p>
        </div>
        <a href="{{ route(auth()->user()->role . '.mata-kuliah.show', $course) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kembali ke mata kuliah</a>
    </div>

    <div class="mb-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
        Peringkat diurutkan berdasarkan jumlah tugas tepat waktu, lalu persentase tepat waktu dari tugas yang sudah dikumpulkan. Tugas yang belum jatuh tempo dan tugas draft tidak dihitung. Lencana diberikan pada 3, 5, dan 10 pengumpulan tepat waktu.
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 font-semibold text-slate-500">Peringkat</th>
                        <th class="px-5 py-4 font-semibold text-slate-500">Mahasiswa</th>
                        <th class="px-5 py-4 font-semibold text-slate-500">Tepat waktu</th>
                        <th class="px-5 py-4 font-semibold text-slate-500">Persentase</th>
                        <th class="px-5 py-4 font-semibold text-slate-500">Lencana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr @class(['bg-blue-50/60' => $student->id === auth()->id()])>
                            <td class="px-5 py-4 font-bold text-slate-900">#{{ $student->rank }}</td>
                            <td class="px-5 py-4 font-medium text-slate-900">
                                {{ $student->name }}
                                @if ($student->id === auth()->id())
                                    <span class="ml-1 text-xs font-normal text-blue-700">(Kamu)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $student->on_time_count }} dari {{ $student->submitted_count }} submission</td>
                            <td class="px-5 py-4 text-slate-700">{{ number_format($student->on_time_percentage, 1) }}%</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($student->badges as $badge)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800" title="{{ $badge['threshold'] }} submission tepat waktu">{{ $badge['name'] }}</span>
                                    @empty
                                        <span class="text-slate-400">Belum ada</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada mahasiswa terdaftar di mata kuliah ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
