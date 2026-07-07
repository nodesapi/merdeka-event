<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = $site?->site_name ?: config('app.name', 'Portal Warga');
    @endphp
    <title>{{ ($title ?? $siteName) }} - Panel Panitia</title>
    @if ($site?->favicon_url)
        <link rel="icon" href="{{ $site->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @php
        $nav = [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard'],
            ['route' => 'admin.event', 'label' => 'Acara & Jadwal', 'match' => 'admin.event'],
            ['route' => 'admin.committee', 'label' => 'Susunan Panitia', 'match' => 'admin.committee'],
            ['route' => 'admin.competitions', 'label' => 'Lomba', 'match' => 'admin.competitions'],
            ['route' => 'admin.participants-index', 'label' => 'Peserta', 'match' => 'admin.participants-index,admin.participants'],
            ['route' => 'admin.family-submissions', 'label' => 'Pendaftaran Warga', 'match' => 'admin.family-submissions'],
            ['route' => 'admin.residents', 'label' => 'Data Warga', 'match' => 'admin.residents'],
            ['route' => 'admin.transactions', 'label' => 'Transaksi Dana', 'match' => 'admin.transactions'],
            ['route' => 'admin.settings', 'label' => 'Pengaturan Website', 'match' => 'admin.settings'],
        ];
        $user = auth()->user();
        $widthClasses = [
            'full' => 'max-w-none',
            '7xl' => 'max-w-7xl',
            '6xl' => 'max-w-6xl',
            '5xl' => 'max-w-5xl',
        ];
        $contentClass = $widthClasses[$contentWidth] ?? $widthClasses['full'];
    @endphp

    <div class="flex min-h-screen" data-admin-shell>
        <div class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden" data-admin-overlay></div>

        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-out lg:static lg:z-auto lg:w-64 lg:translate-x-0 lg:shadow-none" data-admin-sidebar>
            <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 lg:px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center" title="{{ $siteName }}">
                    @if ($site?->logo_url)
                        <img src="{{ $site->logo_url }}" alt="{{ $siteName }}" class="h-12 w-auto max-w-[190px] object-contain">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-600 text-xl font-bold text-white">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                    @endif
                </a>
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 lg:hidden" data-admin-close aria-label="Tutup menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <nav class="space-y-1 p-4">
                    @foreach ($nav as $item)
                        @php $active = collect(explode(',', $item['match']))->contains(fn ($r) => request()->routeIs(trim($r))); @endphp
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition {{ $active ? 'bg-red-50 text-red-700 ring-1 ring-red-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="h-2.5 w-2.5 rounded-full {{ $active ? 'bg-red-600' : 'bg-slate-300' }}"></span>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach

                    <a href="{{ route('public.home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="truncate">Lihat Halaman Warga</span>
                    </a>
                </nav>
            </div>

            <div class="border-t border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 font-semibold text-red-700">
                        {{ strtoupper(substr($user?->name ?? 'A', 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $user?->name ?? 'Admin Warga' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user?->email ?? '-' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-red-600">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 lg:hidden" data-admin-open aria-label="Buka menu">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900 sm:text-lg">{{ $header ?? 'Dashboard' }}</p>
                            <p class="hidden text-xs text-slate-500 sm:block">Panel pengelolaan acara, panitia, lomba, dan website warga.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden rounded-full bg-red-100 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-red-800 sm:inline-flex">
                            {{ $user ? strtoupper($user->getRoleNames()->implode(', ')) : 'Panitia Merdeka' }}
                        </span>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-sm font-semibold text-red-700 sm:hidden">
                            {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="mx-auto w-full {{ $contentClass }} px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
