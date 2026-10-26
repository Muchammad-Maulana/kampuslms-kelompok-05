<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'EduKampus' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
/>
</head>

<body class="min-h-screen bg-slate-100 font-['Inter'] text-slate-800">

    <div class="min-h-screen flex">

        {{-- Sidebar digunakan sebagai navigasi utama aplikasi. --}}
        <aside class="hidden lg:flex fixed left-0 top-0 h-screen w-64 bg-slate-950 text-slate-300 flex-col justify-between p-6 z-50">

            <div>
                {{-- Logo dan nama aplikasi. --}}
                <div class="flex items-center gap-3 px-2 mb-10">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                        E
                    </div>

                    <div>
                        <h1 class="text-xl font-extrabold text-white">
                            EduKampus
                        </h1>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest">
                            Portal Akademik
                        </p>
                    </div>
                </div>

                {{-- Menu navigasi menggunakan route() agar URL tidak ditulis manual. --}}
                <nav class="space-y-2">
    
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3
                        {{ request()->routeIs('dashboard')
                            ? 'bg-blue-600 text-white font-semibold shadow-lg'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="material-symbols-outlined">
                            dashboard
                        </span>

                        Dashboard
                    </a>

                    @if (auth()->user()?->role === 'admin')
                    <a
                        href="{{ route('admin.mata-kuliah.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3
                        {{ request()->routeIs('admin.mata-kuliah.*')
                            ? 'bg-blue-600 text-white font-semibold shadow-lg'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="material-symbols-outlined">
                            menu_book
                        </span>

                        Mata Kuliah
                    </a>
                    @endif

                    @if (in_array(auth()->user()?->role, ['dosen', 'mahasiswa'], true))
                        @php
                            $courseRoute = auth()->user()->role === 'dosen'
                                ? 'dosen.mata-kuliah.index'
                                : 'mahasiswa.mata-kuliah.index';
                            $courseRoutePattern = auth()->user()->role . '.mata-kuliah.*';
                        @endphp
                        <a
                            href="{{ route($courseRoute) }}"
                            class="flex items-center gap-3 rounded-xl px-4 py-3
                            {{ request()->routeIs($courseRoutePattern)
                                ? 'bg-blue-600 text-white font-semibold shadow-lg'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <span class="material-symbols-outlined">menu_book</span>
                            Mata Kuliah
                        </a>
                    @endif

                    @if (auth()->user()?->role === 'mahasiswa')
                        <a
                            href="{{ route('mahasiswa.grades.index') }}"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('mahasiswa.grades.*') ? 'bg-blue-600 text-white font-semibold shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <span class="material-symbols-outlined">grade</span>
                            Rekap Nilai
                        </a>
                    @endif

                    <a
                        href="{{ route('tentang') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3
                        {{ request()->routeIs('tentang')
                            ? 'bg-blue-600 text-white font-semibold shadow-lg'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="material-symbols-outlined">
                            info
                        </span>

                        Tentang
                    </a>

                    @if (auth()->user()?->role === 'admin')
                        <a
                        href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3
                        {{ request()->routeIs('admin.users.*')
                            ? 'bg-blue-600 text-white font-semibold shadow-lg'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="material-symbols-outlined">
                            group
                        </span>

                        Pengguna
                    </a>
                    @endif

                </nav>

            {{-- Informasi tambahan pada bagian bawah sidebar. --}}
            <div class="border-t border-slate-800 pt-5">
                <p class="text-xs text-slate-500">
                    T.A. 2024/2025 Genap
                </p>

                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    Sistem Aktif
                </div>
            </div>

        </aside>


        {{-- Area utama halaman berada di sebelah sidebar. --}}
        <div class="w-full lg:pl-64">

            {{-- Header digunakan untuk pencarian dan informasi pengguna. --}}
            <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
                <div class="h-16 px-6 lg:px-8 flex items-center justify-between gap-4">

                    <div class="relative w-full max-w-md">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            ⌕
                        </span>

                        <input
                            type="search"
                            placeholder="Cari mata kuliah, materi, dosen..."
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border-0 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    @auth
                    @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
                    <div class="hidden md:flex items-center gap-3">
                        <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Notifikasi">
                            <span class="material-symbols-outlined align-middle">notifications</span>
                            <span id="notification-count" class="{{ $unreadNotificationCount ? '' : 'hidden' }} absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-5 text-white" aria-live="polite">{{ $unreadNotificationCount }}</span>
                        </a>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ auth()->user()->role === 'mahasiswa' ? 'Mahasiswa' : ucfirst(auth()->user()->role) }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Keluar</button>
                        </form>
                    </div>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Masuk</a>
                    @endauth

                </div>
            </header>

            @auth
                <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-white px-4 py-2 lg:hidden" aria-label="Navigasi utama">
                    <a href="{{ route('dashboard') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Dashboard</a>
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.mata-kuliah.index') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.mata-kuliah.*') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Mata Kuliah</a>
                        <a href="{{ route('admin.users.index') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.users.*') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Pengguna</a>
                    @else
                        <a href="{{ route(auth()->user()->role . '.mata-kuliah.index') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs(auth()->user()->role . '.mata-kuliah.*') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Mata Kuliah</a>
                    @endif
                    @if (auth()->user()->role === 'mahasiswa')
                        <a href="{{ route('mahasiswa.grades.index') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('mahasiswa.grades.*') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Rekap Nilai</a>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('notifications.*') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        Notifikasi
                        <span id="mobile-notification-count" class="{{ $unreadNotificationCount ? '' : 'hidden' }} ml-1 rounded-full bg-red-600 px-1.5 py-0.5 text-xs text-white">{{ $unreadNotificationCount }}</span>
                    </a>
                    <a href="{{ route('tentang') }}" class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('tentang') ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Tentang</a>
                </nav>
            @endauth


            {{-- Isi halaman dari setiap Blade component ditampilkan melalui $slot. --}}
            <main class="p-6 lg:p-8">
                {{ $slot }}
            </main>

        </div>

    </div>

    @auth
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const badge = document.getElementById('notification-count');
                const mobileBadge = document.getElementById('mobile-notification-count');

                const refreshUnreadCount = async () => {
                    try {
                        const response = await fetch(@json(route('notifications.unread-count')), {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) {
                            throw new Error(`Notification count request failed with ${response.status}`);
                        }

                        const { count } = await response.json();
                        if (badge) {
                            badge.textContent = count;
                            badge.classList.toggle('hidden', count === 0);
                        }
                        if (mobileBadge) {
                            mobileBadge.textContent = count;
                            mobileBadge.classList.toggle('hidden', count === 0);
                        }
                    } catch (error) {
                        console.error('Unable to refresh notification count.', error);
                    }
                };

                window.setInterval(refreshUnreadCount, 30000);
            });
        </script>
    @endauth

</body>
</html>