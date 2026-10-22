    @php
        $role = auth()->user()->role;
        $roleLabel = $role === 'mahasiswa' ? 'Mahasiswa' : ucfirst($role);
        $roleDescription = match ($role) {
            'admin' => 'Kelola pengguna dan seluruh data akademik dalam satu portal.',
            'dosen' => 'Kelola mata kuliah dan pantau aktivitas pembelajaran Anda.',
            default => 'Jelajahi mata kuliah dan pantau aktivitas akademik Anda.',
        };
    @endphp

    <x-layout title="Dashboard {{ $roleLabel }}">

        <div class="w-full px-6 lg:px-8 py-6">

            <div class="flex flex-col gap-6">

                <section
                    class="relative
                        rounded-2xl
                        overflow-hidden
                        bg-gradient-to-br
                        from-[#4648d4]
                        via-[#6063ee]
                        to-[#6b38d4]
                        p-8
                        text-white
                        shadow-lg"
                >

                    <div class="relative z-10">

                        <span
                            class="inline-flex items-center gap-2
                                px-3 py-1
                                rounded-full
                                bg-white/15
                                text-[11px]
                                uppercase
                                tracking-wider"
                        >

                            <span class="material-symbols-outlined text-[16px]">
                                verified
                            </span>

                            Portal Akademik

                        </span>


                        <h1
                            class="text-3xl
                                font-bold
                                tracking-tight
                                mt-4"
                        >
                            Selamat datang, {{ auth()->user()->name }}
                        </h1>


                        <p
                            class="text-[#e1e0ff]
                                mt-2
                                max-w-2xl"
                        >
                            {{ $roleDescription }}
                        </p>

                    </div>

                </section>

                <div
                    class="grid grid-cols-1
                        md:grid-cols-3
                        gap-4"
                >

                    <div
                        class="bg-white
                            rounded-xl
                            p-5
                            shadow-sm"
                    >

                        <span
                            class="material-symbols-outlined
                                text-[#4648d4]"
                        >
                            menu_book
                        </span>

                        <p class="text-sm text-[#464554] mt-3">
                            Mata Kuliah
                        </p>

                        <p class="text-2xl font-bold mt-1">
                            {{ $courseCount }}
                        </p>

                    </div>


                    <div
                        class="bg-white
                            rounded-xl
                            p-5
                            shadow-sm"
                    >

                        <span
                            class="material-symbols-outlined
                                text-[#6b38d4]"
                        >
                            school
                        </span>

                        <p class="text-sm text-[#464554] mt-3">
                            Total SKS
                        </p>

                        <p class="text-2xl font-bold mt-1">
                            {{ $totalSks }}
                        </p>

                    </div>


                    <div
                        class="bg-white
                            rounded-xl
                            p-5
                            shadow-sm"
                    >

                        <span
                            class="material-symbols-outlined
                                text-[#00628d]"
                        >
                            assignment
                        </span>

                        <p class="text-sm text-[#464554] mt-3">
                            Status akses
                        </p>

                        <p class="text-2xl font-bold mt-1">
                            {{ $roleLabel }}
                        </p>

                    </div>

                </div>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Mata Kuliah Terbaru</h2>
                            <p class="mt-1 text-sm text-slate-500">Course yang tersedia sesuai akses akun Anda.</p>
                        </div>
                        <a href="{{ route($courseIndexRoute) }}" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Buka daftar mata kuliah</a>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($recentCourses as $course)
                            <a href="{{ route($role . '.mata-kuliah.show', $course) }}" class="flex flex-wrap items-center justify-between gap-3 py-3 hover:bg-slate-50">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $course->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $course->code }} · {{ $course->lecturer?->name ?? 'Belum ditentukan' }}</p>
                                </div>
                                <span class="text-sm font-semibold text-blue-700">Lihat detail</span>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Belum ada mata kuliah yang tersedia untuk akun ini.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-blue-600">account_circle</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Anda masuk sebagai {{ $roleLabel }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </section>

            </div>

        </div>

    </x-layout>