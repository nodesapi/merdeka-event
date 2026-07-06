@props(['title' => null, 'eventName' => null])

@php
    $siteName = $site?->site_name ?: ($eventName ?: config('app.name', 'Portal Warga'));
    $tagline = $site?->tagline ?: 'Portal Informasi Warga';
    $metaTitle = $title ? $title . ' - ' . $siteName : $siteName;
    $metaDescription = $site?->tagline ?: 'Portal informasi warga, panitia, lomba, dan transparansi dana kegiatan kemerdekaan.';
    $metaUrl = url()->current();
    $metaImage = $site?->og_image_url ? url($site->og_image_url) : ($site?->hero_banner_url ? url($site->hero_banner_url) : null);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-clip">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($site?->google_site_verification)
        <meta name="google-site-verification" content="{{ $site->google_site_verification }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $metaUrl }}">
    @if ($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $metaImage }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if ($site?->favicon_url)
        <link rel="icon" href="{{ $site->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen flex flex-col overflow-x-clip">
    <div class="merdeka-ribbon h-1.5 w-full"></div>

    @php
        $navItems = [
            ['route' => 'public.home', 'label' => 'Beranda', 'short' => 'Beranda', 'icon' => 'home'],
            ['route' => 'public.committee', 'label' => 'Panitia', 'short' => 'Panitia', 'icon' => 'users'],
            ['route' => 'public.competitions', 'label' => 'Lomba', 'short' => 'Lomba', 'icon' => 'trophy'],
            ['route' => 'public.finance', 'label' => 'Laporan', 'short' => 'Laporan', 'icon' => 'wallet'],
            ['route' => 'public.family-form', 'label' => 'Form Warga', 'short' => 'Form', 'icon' => 'clipboard'],
        ];
    @endphp

    <header class="sticky top-0 z-30 overflow-x-clip border-b border-stone-200/70 bg-white/90 backdrop-blur">
        <div class="mx-auto flex min-w-0 max-w-6xl items-center justify-between gap-3 px-4 py-3.5 sm:px-5 lg:px-8">
            <a href="{{ route('public.home') }}" class="flex min-w-0 flex-1 items-center overflow-hidden">
                @if ($site?->logo_url)
                    <img src="{{ $site->logo_url }}" alt="Logo" class="h-14 w-auto max-w-full shrink object-contain sm:h-20 sm:max-w-[300px]">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-red-700 text-3xl font-black text-white sm:h-20 sm:w-20">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                @endif
            </a>

            <div class="flex shrink-0 items-center gap-1.5">
                <nav class="hidden items-center gap-0.5 md:flex">
                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['route']) || ($item['route'] === 'public.competitions' && request()->routeIs('public.competition.show')); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-red-50 text-red-700' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <a href="{{ route('admin.dashboard') }}" class="shrink-0 rounded-lg bg-red-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-red-800">
                    Admin
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-8 text-center lg:px-8 lg:py-10 md:text-left">
        {{ $slot }}
    </main>

    <div class="mt-10 w-full overflow-hidden leading-none" aria-hidden="true">
        <img src="/banner/siluet-hut-ri.png" alt="" class="pointer-events-none mx-auto block h-auto w-full max-w-[1536px] select-none">
    </div>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-5 py-8 text-center sm:grid-cols-2 sm:text-left lg:grid-cols-3 lg:px-8">
            <div>
                <p class="text-sm font-extrabold text-stone-900">{{ $siteName }}</p>
                <p class="mt-1 text-sm text-stone-500">{{ $tagline }}</p>
            </div>

            @if ($site?->contact_whatsapp)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Kontak Panitia</p>
                    <a href="{{ $site->whatsapp_url }}" target="_blank" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:underline">
                        WhatsApp: {{ $site->contact_whatsapp }}
                    </a>
                    @if ($site->contact_person)
                        <p class="mt-1 text-sm text-stone-500">a/n {{ $site->contact_person }}</p>
                    @endif
                </div>
            @endif

            @if ($site?->bank_account_number)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Rekening Iuran / Sumbangan</p>
                    <p class="mt-2 text-sm font-bold text-stone-900">{{ $site->bank_name }} · {{ $site->bank_account_number }}</p>
                    @if ($site->bank_account_holder)
                        <p class="mt-1 text-sm text-stone-500">a/n {{ $site->bank_account_holder }}</p>
                    @endif
                </div>
            @endif
        </div>
        <div class="border-t border-stone-200/70">
            <div class="mx-auto max-w-6xl px-5 py-4 text-center text-xs text-stone-400 lg:px-8">
                <span class="inline-flex items-center gap-1.5">&copy; {{ date('Y') }} {{ $siteName }}. Dikelola bersama untuk warga. <x-icon name="flag" class="h-3.5 w-3.5 text-red-600" /></span>
            </div>
        </div>
    </footer>

    <div class="h-24 md:hidden" aria-hidden="true"></div>

    <nav data-bottom-nav class="fixed inset-x-3 bottom-3 z-40 mx-auto flex max-w-md items-center justify-around gap-0.5 overflow-hidden rounded-2xl border border-stone-200 bg-white/95 p-1.5 shadow-[0_8px_30px_rgba(0,0,0,0.12)] backdrop-blur md:hidden">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']) || ($item['route'] === 'public.competitions' && request()->routeIs('public.competition.show')); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex min-w-0 flex-1 flex-col items-center gap-1 rounded-xl px-1 py-1.5 text-[10px] font-semibold transition {{ $active ? 'bg-red-600 text-white shadow-sm' : 'text-stone-500 hover:text-red-700' }}">
                <x-icon :name="$item['icon']" class="h-5 w-5" />
                <span class="truncate">{{ $item['short'] }}</span>
            </a>
        @endforeach
    </nav>

    <script>
        (function () {
            var nav = document.querySelector('[data-bottom-nav]');
            if (!nav) return;
            function place() {
                var vw = document.documentElement.clientWidth || window.innerWidth;
                var w = Math.min(vw - 24, 448);
                nav.style.width = w + 'px';
                nav.style.left = Math.round((vw - w) / 2) + 'px';
                nav.style.right = 'auto';
                nav.style.marginLeft = '0';
                nav.style.marginRight = '0';
            }
            place();
            window.addEventListener('resize', place);
            window.addEventListener('orientationchange', place);
            window.addEventListener('load', place);
        })();
    </script>
</body>
</html>
