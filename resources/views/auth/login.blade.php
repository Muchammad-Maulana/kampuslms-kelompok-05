<x-layout title="Login">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Akses Portal</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Login KampusLMS</h1>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="nama@kampuslms.test">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="Password">
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-semibold text-slate-700">Role</label>
                <select id="role" name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:outline-none">
                    <option value="">Pilih role</option>
                    <option value="mahasiswa" @selected(old('role') === 'mahasiswa')>Mahasiswa</option>
                    <option value="dosen" @selected(old('role') === 'dosen')>Dosen</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Masuk</button>
        </form>

        <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Demo akun</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Mahasiswa: mahasiswa@kampuslms.test / password</li>
                <li>Dosen: dosen@kampuslms.test / password</li>
                <li>Admin: admin@kampuslms.test / password</li>
            </ul>
        </div>
    </div>
</x-layout>
