<x-layouts.public :eventName="$event?->name">
    @php
        $anniversary = ($event?->start_date?->year ?? now()->year) - 1945;
        $isUpcoming = $event?->start_date && $event->start_date->isFuture();
    @endphp

    <div class="relative" data-merdeka-hero>
        <canvas class="merdeka-celebration-layer" data-merdeka-celebration aria-hidden="true"></canvas>

        @if ($site?->hero_banner_url)
            <div class="relative z-[2] left-1/2 -mt-8 w-screen -translate-x-1/2 bg-stone-950 lg:-mt-10">
                <img src="{{ $site->hero_banner_url }}" alt="Banner {{ $site?->site_name ?? $event?->name }}" class="block h-auto w-full">
            </div>
        @else
            <section class="relative z-[2] left-1/2 -mt-8 w-screen -translate-x-1/2 overflow-hidden bg-[#7d0a12] text-white lg:-mt-10">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_120%_at_15%_-10%,#c1121f_0%,#9a0e18_38%,#5f070d_100%)]"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-[radial-gradient(circle,rgba(244,185,66,0.35),transparent_60%)]"></div>
                <div class="pointer-events-none absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.12),transparent_65%)]"></div>

                {{-- Ornamen: logo HUT RI di belakang judul --}}
                <img src="/banner/logo_hut_ri_81.webp" alt="" aria-hidden="true" class="pointer-events-none absolute left-2 top-1/2 hidden w-36 -translate-y-1/2 select-none opacity-20 sm:block lg:left-8 lg:w-56">
                {{-- Ornamen: Garuda Pancasila samar, agak ke tengah (boleh sedikit di belakang kartu hitung mundur) --}}
                <img src="/banner/logo_garuda_indonesia.webp" alt="" aria-hidden="true" class="pointer-events-none absolute right-4 top-1/2 hidden w-40 -translate-y-1/2 select-none opacity-[0.12] sm:block lg:right-24 lg:w-60">

                {{-- Ornamen: bunting bendera merah-putih-emas di atas --}}
                <svg class="pointer-events-none absolute inset-x-0 top-0 h-8 w-full select-none" aria-hidden="true">
                    <defs>
                        <pattern id="merdeka-bunting" width="44" height="32" patternUnits="userSpaceOnUse">
                            <line x1="0" y1="2" x2="44" y2="2" stroke="rgba(255,255,255,.4)" stroke-width="1.5" />
                            <polygon points="5,2 21,2 13,23" fill="#ffffff" opacity="0.92" />
                            <polygon points="23,2 39,2 31,23" fill="#f4b942" opacity="0.92" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="32" fill="url(#merdeka-bunting)" />
                </svg>

                <div class="relative mx-auto grid max-w-6xl gap-10 px-5 py-12 lg:grid-cols-[1.35fr_0.9fr] lg:items-center lg:px-8 lg:py-16">
                    <div>
                        <h1 class="text-4xl font-black leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                            {{ $site?->site_name ?: ($event?->name ?? 'Pesta Rakyat Kemerdekaan') }}
                        </h1>
                        <p class="mt-5 max-w-xl text-[15px] leading-7 text-red-50/90">
                            {{ $event?->description ?? 'Panggung informasi warga untuk melihat susunan panitia, jenis lomba, jalannya babak, hingga arus dana secara terbuka.' }}
                        </p>

                        <div class="mt-7 grid grid-cols-2 gap-3">
                            <a href="{{ route('public.family-form') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#9a0e18] shadow-lg shadow-black/20 transition hover:bg-amber-50">
                                <x-icon name="users" class="h-4 w-4 shrink-0" /> Isi Form Warga
                            </a>
                            <a href="{{ route('public.competitions') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#9a0e18] shadow-lg shadow-black/20 transition hover:bg-amber-50">
                                <x-icon name="trophy" class="h-4 w-4 shrink-0" /> Lihat Lomba
                            </a>
                            <a href="{{ route('public.committee') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/5 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                                Susunan Panitia
                            </a>
                            <a href="{{ route('public.galeri') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/5 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                                <x-icon name="image" class="h-4 w-4 shrink-0" /> Keseruan HUT RI
                            </a>
                            <a href="{{ route('public.bazaar-form') }}" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#9a0e18] shadow-lg shadow-black/20 transition hover:bg-amber-50">
                                <x-icon name="cash" class="h-4 w-4 shrink-0" /> Daftar Bazaar
                            </a>
                        </div>

                        @if ($event)
                            <div class="mt-8 flex flex-wrap justify-center gap-x-10 gap-y-4 md:justify-start">
                                <div class="flex flex-col items-center gap-1 text-center md:flex-row md:items-start md:gap-2.5 md:text-left">
                                    <x-icon name="map-pin" class="mt-0.5 h-5 w-5 text-red-200" />
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-red-100/80">Lokasi</p>
                                        <p class="text-sm font-semibold">{{ $event->location }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center gap-1 text-center md:flex-row md:items-start md:gap-2.5 md:text-left">
                                    <x-icon name="calendar" class="mt-0.5 h-5 w-5 text-red-200" />
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-red-100/80">Jadwal</p>
                                        <p class="text-sm font-semibold">{{ $event->schedule_label }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-6 shadow-2xl shadow-black/30 backdrop-blur">
                        @if ($isUpcoming)
                            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-amber-200"><x-icon name="clock" class="h-4 w-4" /> Hitung Mundur Menuju Hari-H</p>
                            <div id="countdown" data-target="{{ $event->start_date->timestamp }}" class="mt-4 grid grid-cols-4 gap-2 text-center">
                                @foreach (['days' => 'Hari', 'hours' => 'Jam', 'mins' => 'Menit', 'secs' => 'Detik'] as $key => $label)
                                    <div class="rounded-xl bg-black/20 py-3">
                                        <span data-cd="{{ $key }}" class="block text-2xl font-black tabular-nums sm:text-3xl">00</span>
                                        <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide text-red-100/80">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-5 space-y-2 border-t border-white/15 pt-4 text-sm">
                                <p class="flex items-center justify-between"><span class="text-red-100/80">Dana tersedia</span><span class="font-black">Rp{{ number_format($balance, 0, ',', '.') }}</span></p>
                                <a href="{{ route('public.finance') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-amber-200 hover:text-amber-100">Lihat transparansi dana <x-icon name="arrow-right" class="h-4 w-4" /></a>
                            </div>
                        @else
                            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-amber-200"><x-icon name="sparkles" class="h-4 w-4" /> Rangkaian Acara</p>
                            <p class="mt-3 text-2xl font-black">Sampai jumpa di kegiatan warga!</p>
                            <div class="mt-5 space-y-2 border-t border-white/15 pt-4 text-sm">
                                <p class="flex items-center justify-between"><span class="text-red-100/80">Dana tersedia</span><span class="font-black">Rp{{ number_format($balance, 0, ',', '.') }}</span></p>
                                <a href="{{ route('public.finance') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-amber-200 hover:text-amber-100">Lihat transparansi dana <x-icon name="arrow-right" class="h-4 w-4" /></a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <script>
                (function () {
                    var el = document.getElementById('countdown');
                    if (!el) return;
                    var target = parseInt(el.dataset.target, 10) * 1000;
                    var pad = function (n) { return String(n).padStart(2, '0'); };
                    var set = function (k, v) { var s = el.querySelector('[data-cd=' + k + ']'); if (s) s.textContent = pad(v); };
                    function tick() {
                        var d = target - Date.now();
                        if (d < 0) d = 0;
                        set('days', Math.floor(d / 86400000));
                        set('hours', Math.floor((d % 86400000) / 3600000));
                        set('mins', Math.floor((d % 3600000) / 60000));
                        set('secs', Math.floor((d % 60000) / 1000));
                    }
                    tick();
                    setInterval(tick, 1000);
                })();
            </script>
        @endif
    </div>

    @if ($event?->registration_closes_at && $event->registration_closes_at->isFuture())
        <section class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
            <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
                <div>
                    <div class="flex flex-col items-center gap-1.5 sm:flex-row sm:items-center sm:gap-2">
                        <x-icon name="clock" class="h-4 w-4 shrink-0 text-amber-700" />
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-amber-700">Batas Pendaftaran &amp; Pengumpulan Dana</p>
                    </div>
                    <p class="mt-1 text-sm text-stone-600">Daftar dan lunasi iuran sebelum {{ $event->registration_closes_at->translatedFormat('d F Y, H:i') }} WIB.</p>
                </div>
                <div id="registration-countdown" data-target="{{ $event->registration_closes_at->timestamp }}" class="grid grid-cols-4 gap-2 text-center">
                    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'mins' => 'Menit', 'secs' => 'Detik'] as $key => $label)
                        <div class="rounded-xl bg-white/70 px-3 py-2">
                            <span data-cd="{{ $key }}" class="block text-xl font-black tabular-nums text-amber-800 sm:text-2xl">00</span>
                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide text-amber-700/70">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <script>
            (function () {
                var el = document.getElementById('registration-countdown');
                if (!el) return;
                var target = parseInt(el.dataset.target, 10) * 1000;
                var pad = function (n) { return String(n).padStart(2, '0'); };
                var set = function (k, v) { var s = el.querySelector('[data-cd=' + k + ']'); if (s) s.textContent = pad(v); };
                function tick() {
                    var d = target - Date.now();
                    if (d < 0) d = 0;
                    set('days', Math.floor(d / 86400000));
                    set('hours', Math.floor((d % 86400000) / 3600000));
                    set('mins', Math.floor((d % 3600000) / 60000));
                    set('secs', Math.floor((d % 60000) / 1000));
                }
                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endif

    @php
        $stats = [
            ['route' => 'public.committee', 'icon' => 'users', 'grad' => 'from-red-500 to-red-700', 'shadow' => 'shadow-red-600/25', 'value' => $committeeCount, 'label' => 'Panitia & tim pelaksana'],
            ['route' => 'public.competitions', 'icon' => 'trophy', 'grad' => 'from-amber-400 to-amber-600', 'shadow' => 'shadow-amber-500/25', 'value' => $competitions->count(), 'label' => 'Jenis lomba warga'],
            ['route' => 'public.finance', 'icon' => 'wallet', 'grad' => 'from-emerald-500 to-emerald-700', 'shadow' => 'shadow-emerald-600/25', 'value' => 'Rp' . number_format($totalIncome, 0, ',', '.'), 'label' => 'Dana terkumpul'],
        ];
    @endphp
    <section class="mt-8 grid gap-4 sm:grid-cols-3">
        @foreach ($stats as $s)
            <a href="{{ route($s['route']) }}" class="merdeka-card group relative overflow-hidden p-5 transition hover:-translate-y-1 hover:shadow-xl">
                <x-icon :name="$s['icon']" class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-stone-950/[0.04]" />
                <div class="relative flex flex-col items-center gap-3 md:flex-row md:gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $s['grad'] }} text-white shadow-lg {{ $s['shadow'] }}">
                        <x-icon :name="$s['icon']" class="h-7 w-7" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-2xl font-black leading-none text-stone-900 sm:text-[26px]">{{ $s['value'] }}</p>
                        <p class="mt-1.5 text-sm text-stone-500">{{ $s['label'] }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </section>

    @if ($schedules->isNotEmpty())
        <section class="mt-10">
            <div class="flex flex-col items-center gap-2 text-center md:flex-row md:items-end md:justify-between md:gap-3 md:text-left">
                <div class="flex items-center gap-3">
                    <span class="hidden h-7 w-1.5 rounded-full bg-red-600 md:block"></span>
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-stone-900">Susunan Acara</h2>
                        <p class="text-sm text-stone-500">Rundown kegiatan hari-H.</p>
                    </div>
                </div>
                <a href="{{ route('public.schedule') }}" class="group inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-red-700 hover:underline">Lihat semua <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></a>
            </div>

            @php
                $scheduleGroups = $event?->is_multi_day
                    ? $schedules->groupBy(fn ($item) => optional($item->scheduled_at)->format('Y-m-d') ?? 'tbd')
                    : null;
            @endphp

            @if ($scheduleGroups)
                <div class="mt-5 space-y-5">
                    @foreach ($scheduleGroups as $dateKey => $daySchedules)
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">
                                @if ($dateKey === 'tbd')
                                    Waktu Belum Ditentukan
                                @else
                                    {{ \Illuminate\Support\Carbon::parse($dateKey)->locale('id')->translatedFormat('l, d F Y') }}
                                @endif
                            </p>
                            <div class="merdeka-card divide-y divide-stone-100 p-0">
                                @foreach ($daySchedules->take(2) as $item)
                                    <div class="flex items-center gap-4 p-4">
                                        <span class="shrink-0 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-black tabular-nums text-red-700">{{ $item->time_label }}</span>
                                        <p class="min-w-0 flex-1 text-left text-sm font-semibold leading-6 text-stone-800">{{ $item->activity }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="merdeka-card mt-5 divide-y divide-stone-100 p-0">
                    @foreach ($schedules->take(5) as $item)
                        <div class="flex items-center gap-4 p-4">
                            <span class="shrink-0 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-black tabular-nums text-red-700">{{ $item->time_label }}</span>
                            <p class="min-w-0 flex-1 text-left text-sm font-semibold leading-6 text-stone-800">{{ $item->activity }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if ($goodyBagItems->isNotEmpty())
        <section class="mt-10">
            <div class="flex flex-col items-center gap-2 text-center md:flex-row md:gap-3 md:text-left">
                <span class="hidden h-7 w-1.5 rounded-full bg-red-600 md:block"></span>
                <div>
                    <h2 class="text-xl font-black tracking-tight text-stone-900">Goody Bag Peserta</h2>
                    <p class="text-sm text-stone-500">Tukarkan No Daftar kamu ke panitia untuk dapat:</p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-center gap-3 sm:justify-start">
                @foreach ($goodyBagItems as $item)
                    <div class="merdeka-card flex w-full items-center gap-3 p-4 sm:w-[calc(50%-0.375rem)] lg:w-[calc(33.333%-0.5rem)]">
                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
                            @if ($item->photo_url)
                                <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-red-500 to-red-700 text-white">
                                    <x-icon name="gift" class="h-6 w-6" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-left text-sm font-black leading-5 text-stone-900">{{ $item->name }}</p>
                            @if ($item->description)
                                <p class="mt-0.5 line-clamp-2 text-left text-xs leading-5 text-stone-500">{{ $item->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10">
        <div class="flex flex-col items-center gap-2 text-center md:flex-row md:items-end md:justify-between md:gap-3 md:text-left">
            <div class="flex items-center gap-3">
                <span class="hidden h-7 w-1.5 rounded-full bg-red-600 md:block"></span>
                <div>
                    <h2 class="text-xl font-black tracking-tight text-stone-900">Jenis Lomba</h2>
                    <p class="text-sm text-stone-500">Klik lomba untuk melihat peserta, babak, dan juaranya.</p>
                </div>
            </div>
            <a href="{{ route('public.competitions') }}" class="group inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-red-700 hover:underline">Lihat semua <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @forelse ($competitions->take(6) as $competition)
                <a href="{{ route('public.competition.show', $competition) }}" class="merdeka-card group relative flex flex-col items-center overflow-hidden p-3.5 pt-5 text-center transition hover:-translate-y-1 hover:border-red-200 hover:shadow-xl sm:p-5 sm:pt-6">
                    <span class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-red-600 via-red-500 to-amber-400"></span>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-md shadow-red-600/20 sm:h-12 sm:w-12 sm:rounded-2xl"><x-icon name="trophy" class="h-5 w-5 sm:h-6 sm:w-6" /></span>
                    <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 sm:mt-2 sm:px-2.5 sm:py-1 sm:text-[11px]"><x-icon name="users" class="h-3 w-3 sm:h-3.5 sm:w-3.5" /> {{ $competition->participants_count }} peserta</span>
                    <h3 class="mt-2.5 line-clamp-2 text-sm font-black text-stone-900 sm:mt-3.5 sm:text-lg">{{ $competition->name }}</h3>
                    <p class="mt-1 line-clamp-1 text-xs font-semibold text-red-700 sm:text-sm">{{ $competition->target_participants }}</p>
                    <p class="mt-2 line-clamp-2 flex-1 text-[11px] leading-5 text-stone-500 sm:text-sm sm:leading-6">{{ $competition->description }}</p>
                    <span class="mt-3 flex w-full items-center justify-center gap-1 border-t border-stone-100 pt-2.5 text-xs font-bold text-red-700 sm:mt-4 sm:pt-3 sm:text-sm">Lihat detail <x-icon name="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-1 sm:h-4 sm:w-4" /></span>
                </a>
            @empty
                <p class="col-span-2 text-sm text-stone-500 lg:col-span-3">Belum ada lomba yang dipublikasikan.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <div class="flex flex-col items-center gap-2 text-center md:flex-row md:items-end md:justify-between md:gap-3 md:text-left">
            <div class="flex items-center gap-3">
                <span class="hidden h-7 w-1.5 rounded-full bg-red-600 md:block"></span>
                <div>
                    <h2 class="text-xl font-black tracking-tight text-stone-900">Peserta Bazaar</h2>
                    <p class="text-sm text-stone-500">
                        {{ $bazaarSubmissionsCount }} dari {{ $bazaarSlotLimit }} lapak terisi
                        @if ($bazaarSlotsRemaining > 0)
                            &middot; sisa <span class="font-bold text-red-700">{{ $bazaarSlotsRemaining }}</span> slot, siapa cepat dia dapat!
                        @else
                            &middot; <span class="font-bold text-red-700">kuota penuh</span>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('public.bazaar-form') }}" class="group inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-red-700 hover:underline">Lihat semua <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @forelse ($bazaarSubmissions as $submission)
                <div class="merdeka-card group relative flex flex-col items-center overflow-hidden p-3.5 pt-5 text-center sm:p-5 sm:pt-6">
                    <span class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-red-600"></span>
                    <x-icon name="storefront" class="pointer-events-none absolute -right-3 -top-2 h-16 w-16 text-red-950/[0.05] sm:h-20 sm:w-20" />
                    <div class="relative flex flex-col items-center">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-red-600 text-white shadow-md shadow-red-600/20 sm:h-12 sm:w-12 sm:rounded-2xl"><x-icon name="storefront" class="h-5 w-5 sm:h-6 sm:w-6" /></span>
                        <h3 class="mt-2.5 line-clamp-1 text-sm font-black text-stone-900 sm:mt-3 sm:text-base">{{ $submission->name }}</h3>
                        @if ($submission->resident_block)
                            <p class="truncate text-[11px] text-stone-500 sm:text-xs">Blok {{ $submission->resident_block }}</p>
                        @endif
                        <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1 sm:mt-3">
                            @foreach ($submission->jenis_jualan_items as $item)
                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 sm:px-2.5 sm:py-1 sm:text-[11px]">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <p class="col-span-2 text-sm text-stone-500 lg:col-span-3">Belum ada warga yang daftar bazaar.</p>
            @endforelse
        </div>
    </section>

    @if ($winners->isNotEmpty())
        @php
            $rankStyle = [
                1 => ['card' => 'border-amber-300 from-amber-50', 'circle' => 'from-amber-400 to-amber-600', 'badge' => 'bg-amber-100 text-amber-800', 'label' => 'Juara 1'],
                2 => ['card' => 'border-slate-300 from-slate-100', 'circle' => 'from-slate-300 to-slate-500', 'badge' => 'bg-slate-200 text-slate-700', 'label' => 'Juara 2'],
                3 => ['card' => 'border-orange-300 from-orange-50', 'circle' => 'from-orange-400 to-orange-600', 'badge' => 'bg-orange-100 text-orange-800', 'label' => 'Juara 3'],
            ];
        @endphp
        <section class="mt-10">
            <div class="flex flex-col items-center gap-2 text-center md:flex-row md:items-center md:gap-3 md:text-left">
                <span class="hidden h-7 w-1.5 rounded-full bg-amber-400 md:block"></span>
                <div>
                    <h2 class="text-xl font-black tracking-tight text-stone-900">Juara Terkini</h2>
                    <p class="text-sm text-stone-500">Selamat kepada para pemenang lomba!</p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($winners as $winner)
                    @php $r = $rankStyle[$winner->rank] ?? $rankStyle[3]; @endphp
                    <a href="{{ route('public.competition.show', $winner->competition) }}" class="group flex flex-col items-center gap-3 rounded-xl border {{ $r['card'] }} bg-gradient-to-br to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md md:flex-row md:gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br {{ $r['circle'] }} text-white shadow-md">
                            <x-icon name="medal" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <span class="inline-flex rounded-full {{ $r['badge'] }} px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">{{ $r['label'] }}</span>
                            <p class="mt-1 truncate font-black text-stone-900">{{ $winner->name }}</p>
                            <p class="truncate text-xs text-stone-500">{{ $winner->competition->name }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($sponsors->isNotEmpty())
        <section class="mt-10">
            <div class="text-center">
                <h2 class="text-xl font-black tracking-tight text-stone-900">Didukung Oleh</h2>
                <p class="mt-1 text-sm text-stone-500">Terima kasih kepada sponsor & pendukung acara ini.</p>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                @foreach ($sponsors as $sponsor)
                    <div class="merdeka-card flex items-center gap-2.5 px-4 py-3">
                        @if ($sponsor->logo_url)
                            <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="h-8 w-8 shrink-0 rounded-lg object-contain sm:h-10 sm:w-10">
                        @else
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-red-500 to-red-700 text-white sm:h-10 sm:w-10"><x-icon name="sparkles" class="h-4 w-4 sm:h-5 sm:w-5" /></span>
                        @endif
                        <span class="text-sm font-bold text-stone-800">{{ $sponsor->name }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
