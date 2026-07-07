<x-layouts.public title="Jenis Lomba" :eventName="$event?->name">
    <div class="flex flex-col items-center gap-3 text-center md:flex-row md:items-center md:text-left">
        <span class="hidden h-8 w-1.5 rounded-full bg-red-600 md:block"></span>
        <div>
            <span class="merdeka-badge">Arena Lomba</span>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Jenis Lomba Warga</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-stone-500">Pilih salah satu lomba untuk melihat daftar peserta, jalannya babak, dan juara.</p>
        </div>
    </div>

    <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-red-200 bg-gradient-to-br from-red-700 to-red-800 p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-icon name="trophy" class="h-6 w-6" /></span>
            <div>
                <p class="text-base font-black">Mau ikut lomba?</p>
                <p class="mt-0.5 text-sm text-red-100">Cukup pakai No Daftar dari Form Warga — pilih lomba sekali klik, tanpa isi ulang data.</p>
            </div>
        </div>
        <a href="{{ route('public.lomba-register') }}" class="shrink-0 rounded-xl bg-white px-5 py-2.5 text-center text-sm font-bold text-red-700 transition hover:bg-red-50">Daftar Lomba &rarr;</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($competitions as $competition)
            <a href="{{ route('public.competition.show', $competition) }}" class="merdeka-card group relative flex flex-col overflow-hidden p-5 transition hover:-translate-y-0.5 hover:border-red-200">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 to-amber-400"></span>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-700"><x-icon name="trophy" class="h-6 w-6" /></span>
                    <span class="shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold text-stone-600">{{ $competition->participants_count }} peserta</span>
                </div>
                <h3 class="mt-3 text-lg font-black text-stone-900">{{ $competition->name }}</h3>
                <p class="mt-1 text-sm font-semibold text-red-700">{{ $competition->target_participants }}</p>
                @if ($competition->age_limit_label)
                    <p class="mt-1 inline-flex w-fit items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">{{ $competition->age_limit_label }}</p>
                @endif
                <p class="mt-2 line-clamp-3 flex-1 text-sm leading-6 text-stone-500">{{ $competition->description }}</p>
                <div class="mt-3 flex items-center justify-between border-t border-stone-100 pt-3 text-sm">
                    <span class="font-medium text-stone-400">{{ $competition->total_rounds }} babak</span>
                    <span class="font-semibold text-red-700 group-hover:underline">Lihat detail &rarr;</span>
                </div>
            </a>
        @empty
            <p class="text-sm text-stone-500">Belum ada lomba yang dipublikasikan.</p>
        @endforelse
    </div>
</x-layouts.public>
