<x-layout title="Notifikasi">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Pemberitahuan</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Notifikasi</h1>
                <p class="mt-1 text-sm text-slate-600">Notifikasi ini hanya dapat dilihat oleh akun Anda.</p>
            </div>
            @if ($notifications->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" type="submit">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isAssignment = ($data['kind'] ?? null) === 'assignment_published';
                    $message = $isAssignment
                        ? 'Tugas baru dipublikasikan'
                        : 'Nilai tugas Anda sudah tersedia';
                @endphp
                <article class="rounded-xl border p-5 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50/60' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                @unless ($notification->read_at)
                                    <span class="h-2 w-2 rounded-full bg-blue-600" aria-label="Belum dibaca"></span>
                                @endunless
                                <p class="font-semibold text-slate-900">{{ $message }}</p>
                            </div>
                            <p class="mt-1 text-sm text-slate-700">{{ $data['assignment_title'] ?? 'Tugas' }} · {{ $data['course_name'] ?? 'Mata kuliah' }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ $notification->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if (!empty($data['url']))
                                <a href="{{ $data['url'] }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800">Buka</a>
                            @endif
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Tandai dibaca</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <p class="font-semibold text-slate-800">Belum ada notifikasi</p>
                    <p class="mt-1 text-sm text-slate-500">Pemberitahuan tugas dan nilai akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-layout>
