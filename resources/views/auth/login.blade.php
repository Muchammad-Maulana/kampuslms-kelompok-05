<x-layout title="Login">
    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            <h1 class="text-2xl font-bold text-slate-900">Login</h1>
            <p class="text-sm text-slate-500 mt-2">Masuk ke Portal Akademik KampusLMS.</p>

            @if ($errors->any())
                <div class="mt-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block mb-1.5 text-sm font-semibold text-slate-700">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label for="password" class="block mb-1.5 text-sm font-semibold text-slate-700">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 transition"
                >
                    Login
                </button>
            </form>
        </div>
    </div>
</x-layout>
