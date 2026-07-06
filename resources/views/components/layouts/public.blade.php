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

        $musicTracks = \App\Models\MusicTrack::orderBy('sort_order')->get();
        $musicUrls = $musicTracks->map(fn ($t) => $t->url)->values();
        $welcomeEnabled = (bool) ($site?->welcome_enabled ?? true);
        $showWelcome = $welcomeEnabled && ($musicUrls->isNotEmpty() || true);
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
                @if ($musicUrls->isNotEmpty())
                    <button type="button" data-music-toggle aria-label="Putar / jeda musik"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition hover:bg-red-100"
                            title="Musik kemerdekaan">
                        <x-icon name="play" data-music-icon-play class="h-4 w-4" />
                        <span data-music-eq class="items-end gap-[2px]" style="display:none;height:15px">
                            <i class="block w-[3px] rounded-full bg-red-600 transition-[height] duration-75" style="height:40%"></i>
                            <i class="block w-[3px] rounded-full bg-red-600 transition-[height] duration-75" style="height:75%"></i>
                            <i class="block w-[3px] rounded-full bg-red-600 transition-[height] duration-75" style="height:55%"></i>
                            <i class="block w-[3px] rounded-full bg-red-600 transition-[height] duration-75" style="height:85%"></i>
                        </span>
                    </button>
                @endif
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

    <div class="md:hidden" style="height:var(--mobile-nav-spacer);" aria-hidden="true"></div>

    <div
        data-bottom-nav
        class="pointer-events-none fixed inset-x-0 z-40 px-3 md:hidden"
        style="bottom:var(--mobile-nav-bottom);"
    >
        <nav class="pointer-events-auto mx-auto grid w-full max-w-[26rem] grid-cols-5 items-center gap-1 overflow-hidden rounded-2xl border border-stone-200 bg-white p-1.5 shadow-[0_8px_30px_rgba(0,0,0,0.12)]">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route']) || ($item['route'] === 'public.competitions' && request()->routeIs('public.competition.show')); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-0.5 py-1.5 text-[10px] font-semibold transition {{ $active ? 'bg-red-600 text-white shadow-sm' : 'text-stone-500 hover:text-red-700' }}">
                    <x-icon :name="$item['icon']" class="h-5 w-5" />
                    <span class="truncate">{{ $item['short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if ($welcomeEnabled || $musicUrls->isNotEmpty())
        <audio id="bg-music" preload="none"></audio>

        @if ($welcomeEnabled)
            <div data-welcome-modal class="fixed inset-0 z-[70] items-center justify-center p-4" style="display:none">
                <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white text-center shadow-2xl ring-1 ring-black/5">
                    <div class="relative overflow-hidden bg-gradient-to-br from-red-600 to-red-800 px-6 py-7 text-white">
                        <h2 class="text-2xl font-black">{{ $site?->welcome_title_text ?? 'Selamat Datang' }}</h2>
                    </div>
                    <div class="px-6 py-6">
                        <p class="text-sm leading-6 text-stone-600">{{ $site?->welcome_message_text ?? 'Selamat datang di portal warga.' }}</p>
                        <button type="button" data-welcome-enter class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-800">
                            <x-icon name="{{ $musicUrls->isNotEmpty() ? 'music' : 'arrow-right' }}" class="h-4 w-4" /> Masuk
                        </button>
                        @if ($musicUrls->isNotEmpty())
                            <p class="mt-2 text-xs text-stone-400">Dengan iringan lagu kemerdekaan</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <script>
            window.__merdekaMusic = @json($musicUrls);
            (function () {
                var tracks = Array.isArray(window.__merdekaMusic) ? window.__merdekaMusic : [];
                var audio = document.getElementById('bg-music');
                var modal = document.querySelector('[data-welcome-modal]');
                var enterBtn = document.querySelector('[data-welcome-enter]');
                var toggleBtn = document.querySelector('[data-music-toggle]');
                var iconPlay = toggleBtn ? toggleBtn.querySelector('[data-music-icon-play]') : null;
                var eq = toggleBtn ? toggleBtn.querySelector('[data-music-eq]') : null;
                var eqBars = eq ? eq.querySelectorAll('i') : [];
                var K_WELCOME = 'merdeka_welcomed', K_INDEX = 'merdeka_music_i', K_PAUSED = 'merdeka_music_paused';

                var index = parseInt(sessionStorage.getItem(K_INDEX) || '0', 10);
                if (isNaN(index) || index < 0 || index >= tracks.length) index = 0;

                // ---- Visualizer (Web Audio; fallback animasi bila tidak tersedia) ----
                var actx = null, analyser = null, freq = null, raf = null, tick = 0;
                var bins = [2, 5, 9, 14];
                function setupAnalyser() {
                    if (actx || !audio) return;
                    try {
                        var Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        actx = new Ctx();
                        var src = actx.createMediaElementSource(audio);
                        analyser = actx.createAnalyser();
                        analyser.fftSize = 64;
                        src.connect(analyser);
                        analyser.connect(actx.destination);
                        freq = new Uint8Array(analyser.frequencyBinCount);
                    } catch (e) { actx = null; analyser = null; }
                }
                function drawEq() {
                    if (!audio || audio.paused) { raf = null; return; }
                    raf = requestAnimationFrame(drawEq);
                    if (analyser) {
                        analyser.getByteFrequencyData(freq);
                        for (var i = 0; i < eqBars.length; i++) {
                            var v = freq[bins[i] || i] || 0;
                            eqBars[i].style.height = Math.max(15, Math.round(v / 255 * 100)) + '%';
                        }
                    } else {
                        tick += 0.18;
                        for (var j = 0; j < eqBars.length; j++) {
                            eqBars[j].style.height = Math.round(30 + 55 * Math.abs(Math.sin(tick + j * 0.9))) + '%';
                        }
                    }
                }

                function reflect() {
                    if (!toggleBtn) return;
                    var playing = audio && !audio.paused;
                    if (iconPlay) iconPlay.classList.toggle('hidden', playing);
                    if (eq) eq.style.display = playing ? 'flex' : 'none';
                    if (playing && !raf) drawEq();
                }
                function load(i) { if (!tracks.length) return; index = ((i % tracks.length) + tracks.length) % tracks.length; audio.src = tracks[index]; sessionStorage.setItem(K_INDEX, index); }
                function play() {
                    if (!tracks.length) return;
                    if (actx && actx.state === 'suspended') actx.resume();
                    audio.play().then(function () { sessionStorage.setItem(K_PAUSED, '0'); reflect(); }).catch(function () { sessionStorage.setItem(K_PAUSED, '1'); reflect(); });
                }
                function pause() { if (audio) audio.pause(); sessionStorage.setItem(K_PAUSED, '1'); reflect(); }
                function playFromGesture() { setupAnalyser(); play(); }

                if (audio && tracks.length) {
                    load(index);
                    audio.addEventListener('ended', function () { load(index + 1); play(); }); // looping playlist
                    audio.addEventListener('play', reflect);
                    audio.addEventListener('pause', reflect);
                    if (toggleBtn) toggleBtn.addEventListener('click', function () { if (audio.paused) playFromGesture(); else pause(); });
                }

                function closeModal() { if (modal) modal.style.display = 'none'; document.body.style.overflow = ''; }
                function openModal() { if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }

                var welcomed = sessionStorage.getItem(K_WELCOME) === '1';
                if (modal && !welcomed) {
                    openModal();
                } else {
                    closeModal();
                    if (tracks.length && sessionStorage.getItem(K_PAUSED) !== '1') play(); // lanjut musik antar halaman
                }

                if (enterBtn) enterBtn.addEventListener('click', function () { sessionStorage.setItem(K_WELCOME, '1'); closeModal(); playFromGesture(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal && modal.style.display === 'flex') { sessionStorage.setItem(K_WELCOME, '1'); closeModal(); } });
                reflect();
            })();
        </script>
    @endif

</body>
</html>
