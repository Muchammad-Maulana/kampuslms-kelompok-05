<x-layout title="Detail Pengguna">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">DETAIL PENGGUNA</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $user->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white">Edit</a>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kembali</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 max-w-2xl rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 max-w-2xl rounded-lg bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-slate-500">Role</dt><dd class="font-semibold capitalize text-slate-900">{{ $user->role }}</dd></div>
            <div><dt class="text-slate-500">Course yang diajar</dt><dd class="font-semibold text-slate-900">{{ $user->taught_courses_count }}</dd></div>
            <div><dt class="text-slate-500">Submission</dt><dd class="font-semibold text-slate-900">{{ $user->submissions_count }}</dd></div>
        </dl>
    </div>

</x-layout>
