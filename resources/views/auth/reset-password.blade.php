<x-layout title="Atur Kata Sandi Baru">
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-slate-900">Atur kata sandi baru</h1>

        @if ($errors->any())
            <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Kata sandi baru</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan kata sandi baru</button>
        </form>
    </div>
</x-layout>