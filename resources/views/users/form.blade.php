<form action="{{ $formAction }}" method="POST" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($formMethod !== 'POST') @method($formMethod) @endif
    @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div><label for="name" class="block text-sm font-semibold text-slate-700">Nama</label><input id="name" name="name" value="{{ old('name', $user?->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
    <div><label for="email" class="block text-sm font-semibold text-slate-700">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
    <div><label for="password" class="block text-sm font-semibold text-slate-700">Password</label><input id="password" type="password" name="password" {{ $user ? '' : 'required' }} class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><p class="mt-1 text-xs text-slate-500">Minimal 8 karakter. Kosongkan saat edit jika tidak ingin mengubahnya.</p></div>
    <div><label for="role" class="block text-sm font-semibold text-slate-700">Role</label><select id="role" name="role" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">@foreach (['admin', 'dosen', 'student'] as $role)<option value="{{ $role }}" @selected(old('role', $user?->role ?? 'student') === $role)>{{ ucfirst($role) }}</option>@endforeach</select></div>
    <div class="flex gap-3"><a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</a><button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button></div>
</form>
