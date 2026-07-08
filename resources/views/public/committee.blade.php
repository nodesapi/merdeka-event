<x-layouts.public title="Susunan Panitia" :eventName="$event?->name">
    @php
        $tier1 = $committee->where('level', 1)->values();
        $tier2 = $committee->where('level', 2)->values();
        $tier3 = $committee->filter(fn ($m) => $m->level >= 3 || $m->level === null)->values();
    @endphp

    {{-- Header --}}
    <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
        <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-center">
            <span class="hidden h-9 w-1.5 rounded-full bg-red-600 sm:block"></span>
            <div>
                <span class="merdeka-badge">Susunan Panitia</span>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Struktur Kepanitiaan</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-stone-500">Tim yang menggerakkan kegiatan 17 Agustusan warga.</p>
            </div>
        </div>
        <span class="shrink-0 rounded-full bg-red-50 px-3.5 py-1.5 text-sm font-semibold text-red-700">{{ $committee->count() }} panitia aktif</span>
    </div>

    @if ($committee->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center text-sm text-stone-500">
            Belum ada susunan panitia yang ditambahkan.
        </div>
    @else
        {{-- ===================== DESKTOP: bagan bergaris ===================== --}}
        <div class="relative left-1/2 mt-8 hidden w-screen -translate-x-1/2 lg:block">
            <div class="overflow-x-auto px-6 pb-4">
                <div class="mx-auto w-max">
                    {{-- Tier 1: Pimpinan --}}
                    @if ($tier1->isNotEmpty())
                        <div class="flex justify-center">
                            @foreach ($tier1 as $member)
                                <div class="px-2"><x-committee-card :member="$member" variant="lead" class="w-52" /></div>
                            @endforeach
                        </div>
                        @if ($tier2->isNotEmpty() || $tier3->isNotEmpty())
                            <div class="org-trunk"></div>
                        @endif
                    @endif

                    {{-- Tier 2: Pengurus Inti --}}
                    @if ($tier2->isNotEmpty())
                        <div class="org-branch">
                            @foreach ($tier2 as $member)
                                <div class="org-node"><x-committee-card :member="$member" variant="core" class="w-52" /></div>
                            @endforeach
                        </div>
                        @if ($tier3->isNotEmpty())
                            <div class="org-trunk"></div>
                        @endif
                    @endif

                    {{-- Tier 3: Koordinator & Seksi --}}
                    @if ($tier3->isNotEmpty())
                        <div class="org-branch">
                            @foreach ($tier3 as $member)
                                <div class="org-node"><x-committee-card :member="$member" class="w-52" /></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== MOBILE/TABLET: daftar bertingkat ===================== --}}
        <div class="mt-8 space-y-8 lg:hidden">
            @foreach ([['t' => $tier1, 'label' => 'Pimpinan', 'dot' => 'bg-amber-400', 'variant' => 'lead'], ['t' => $tier2, 'label' => 'Pengurus Inti', 'dot' => 'bg-red-500', 'variant' => 'core'], ['t' => $tier3, 'label' => 'Koordinator & Seksi', 'dot' => 'bg-stone-400', 'variant' => 'default']] as $group)
                @if ($group['t']->isNotEmpty())
                    <section>
                        <div class="mb-3 flex items-center gap-2.5">
                            <span class="h-5 w-1.5 rounded-full {{ $group['dot'] }}"></span>
                            <h2 class="text-xs font-black uppercase tracking-[0.14em] text-stone-500">{{ $group['label'] }}</h2>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($group['t'] as $member)
                                <x-committee-card :member="$member" :variant="$group['variant']" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</x-layouts.public>
