<x-layout title="Lupa Kata Sandi">
    <div class="mx-auto max-w-md">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-700">Kembali ke login</a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Lupa kata sandi?</h1>
        <p class="mt-2 text-sm text-slate-600">Masukkan email akun. Jika terdaftar, instruksi reset akan dikirim.</p>

        @if (session('status'))
            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @error('email')
            <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Kirim instruksi reset</button>
        </form>
    </div>
</x-layout>