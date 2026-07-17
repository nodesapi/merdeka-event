<x-layouts.public :title="$competition->name" :eventName="$event?->name">
    @php
        $rankLabels = [1 => 'Juara 1', 2 => 'Juara 2', 3 => 'Juara 3'];
        $rankStyles = [
            1 => 'border-amber-300 bg-amber-50 text-amber-800',
            2 => 'border-stone-300 bg-stone-100 text-stone-700',
            3 => 'border-orange-300 bg-orange-50 text-orange-800',
        ];
        $rankColor = [1 => 'text-amber-500', 2 => 'text-slate-400', 3 => 'text-orange-500'];
    @endphp

    <a href="{{ route('public.competitions') }}" class="text-sm font-semibold text-red-700 hover:underline">&larr; Kembali ke daftar lomba</a>

    {{-- Header --}}
    <div class="mt-4 merdeka-card overflow-hidden">
        <div class="border-b border-stone-100 bg-gradient-to-br from-red-50 to-white p-5 sm:p-6">
            <span class="merdeka-badge">Detail Lomba</span>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h1 class="flex items-center gap-3 text-2xl font-black tracking-tight text-stone-900">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700"><x-icon name="trophy" class="h-6 w-6" /></span>
                        <span class="min-w-0 break-words">{{ $competition->name }}</span>
                    </h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-xs font-bold text-red-700">
                            <x-icon name="users" class="h-3.5 w-3.5" /> {{ $competition->age_limit_label ?: 'Terbuka semua umur' }}
                        </span>
                        @if ($competition->target_participants)
                            <span class="inline-flex items-center rounded-full border border-stone-200 bg-white px-3 py-1 text-xs font-semibold text-stone-600">{{ $competition->target_participants }}</span>
                        @endif
                    </div>
                    @if ($competition->description)
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-500">{{ $competition->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stat strip --}}
        <div class="grid grid-cols-3 divide-x divide-stone-100">
            @if ($competition->isGroup())
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $teams->count() }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Tim</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $competition->total_rounds }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Babak</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $teams->sum(fn ($t) => $t->members->count()) }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Peserta</p>
                </div>
            @else
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $competition->participants->count() }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Peserta</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $competition->total_rounds }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Babak</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-2xl font-extrabold text-stone-900">{{ $participantsByCategory->count() }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Kategori</p>
                </div>
            @endif
        </div>
    </div>

    @if ($competition->isGroup())
        {{-- Winners (tim) --}}
        @if ($winnerTeams->isNotEmpty())
            <section class="mt-6">
                <h2 class="flex items-center gap-2 text-lg font-extrabold tracking-tight text-stone-900"><x-icon name="trophy" class="h-5 w-5 text-amber-500" /> Papan Juara</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($winnerTeams as $team)
                        <article class="rounded-xl border p-4 text-center shadow-sm {{ $rankStyles[$team->rank] ?? 'border-stone-200 bg-white text-stone-700' }}">
                            <x-icon name="medal" class="mx-auto h-9 w-9 {{ $rankColor[$team->rank] ?? 'text-stone-400' }}" />
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide">{{ $rankLabels[$team->rank] ?? ('Peringkat ' . $team->rank) }}</p>
                            <p class="mt-1 text-base font-extrabold text-stone-900">{{ $team->display_name }}</p>
                            <p class="text-sm text-stone-500">{{ $team->members->pluck('name')->join(', ') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Daftar tim --}}
        <section class="mt-8">
            <h2 class="text-lg font-extrabold tracking-tight text-stone-900">Daftar Tim</h2>
            <p class="mt-1 text-sm text-stone-500">Status &amp; babak diperbarui oleh panitia.</p>

            <div class="mt-5 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($teams as $team)
                    <article class="rounded-xl border border-stone-200 bg-white p-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-stone-900">{{ $team->display_name }}</p>
                            @if ($team->rank)
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $rankStyles[$team->rank] ?? 'border-stone-200 text-stone-600' }}"><x-icon name="medal" class="h-3.5 w-3.5 {{ $rankColor[$team->rank] ?? 'text-stone-400' }}" /> {{ $rankLabels[$team->rank] ?? ('Juara ' . $team->rank) }}</span>
                            @elseif ($team->status === 'eliminated')
                                <span class="shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-stone-500">Gugur</span>
                            @else
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">Lolos</span>
                            @endif
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-stone-400">{{ $team->members->count() }} anggota</span>
                            <span class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-700">Babak {{ $team->round }}{{ $team->round == $competition->total_rounds ? ' · Final' : '' }}</span>
                        </div>
                        <p class="mt-2 text-xs text-stone-500">{{ $team->members->pluck('name')->join(', ') }}</p>
                    </article>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3 merdeka-card p-8 text-center text-sm text-stone-500">Belum ada tim yang terdaftar untuk lomba ini.</div>
                @endforelse
            </div>
        </section>
    @else

    {{-- Winners (per kategori umur) --}}
    @if ($winnersByCategory->isNotEmpty())
        <section class="mt-6">
            <h2 class="flex items-center gap-2 text-lg font-extrabold tracking-tight text-stone-900"><x-icon name="trophy" class="h-5 w-5 text-amber-500" /> Papan Juara</h2>
            @foreach ($winnersByCategory as $catWinners)
                <div class="mt-4">
                    @if ($winnersByCategory->count() > 1)
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-bold text-stone-600"><span class="h-4 w-1.5 rounded-full bg-red-600"></span>Kategori {{ $catWinners->first()->age_category_label }}</h3>
                    @endif
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($catWinners as $winner)
                            <article class="rounded-xl border p-4 text-center shadow-sm {{ $rankStyles[$winner->rank] ?? 'border-stone-200 bg-white text-stone-700' }}">
                                <x-icon name="medal" class="mx-auto h-9 w-9 {{ $rankColor[$winner->rank] ?? 'text-stone-400' }}" />
                                <p class="mt-1 text-xs font-bold uppercase tracking-wide">{{ $rankLabels[$winner->rank] ?? ('Peringkat ' . $winner->rank) }}</p>
                                <p class="mt-1 text-base font-extrabold text-stone-900">{{ $winner->name }}</p>
                                <p class="text-sm text-stone-500">{{ $winner->resident_block ?: '-' }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    {{-- Participants per age category --}}
    <section class="mt-8">
        <h2 class="text-lg font-extrabold tracking-tight text-stone-900">Peserta per Kategori Umur</h2>
        <p class="mt-1 text-sm text-stone-500">Peserta dikelompokkan otomatis per kategori umur agar adil. Status &amp; babak diperbarui oleh panitia.</p>

        @forelse ($participantsByCategory as $participants)
            <div class="mt-5 merdeka-card overflow-hidden">
                {{-- Category header --}}
                <div class="flex items-center gap-2.5 border-b border-stone-100 bg-stone-50 px-4 py-3 sm:px-5">
                    <span class="h-5 w-1.5 rounded-full bg-red-600"></span>
                    <h3 class="font-extrabold text-stone-900">Kategori {{ $participants->first()->age_category_label }}</h3>
                    <span class="rounded-full bg-white px-2.5 py-0.5 text-[11px] font-bold text-stone-600 ring-1 ring-stone-200">{{ $participants->count() }} peserta</span>
                </div>

                <div class="grid gap-2.5 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-3">
                    @foreach ($participants as $participant)
                        <article class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 bg-white p-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-stone-900">{{ $participant->name }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-stone-400">{{ $participant->resident_block ?: '-' }}</span>
                                    <span class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-700">Babak {{ $participant->round }}{{ $participant->round == $competition->total_rounds ? ' · Final' : '' }}</span>
                                </div>
                            </div>
                            @if ($participant->rank)
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $rankStyles[$participant->rank] ?? 'border-stone-200 text-stone-600' }}"><x-icon name="medal" class="h-3.5 w-3.5 {{ $rankColor[$participant->rank] ?? 'text-stone-400' }}" /> {{ $rankLabels[$participant->rank] ?? ('Juara ' . $participant->rank) }}</span>
                            @elseif ($participant->status === 'eliminated')
                                <span class="shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-stone-500">Gugur</span>
                            @else
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">Lolos</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="mt-4 merdeka-card p-8 text-center text-sm text-stone-500">Belum ada peserta yang terdaftar untuk lomba ini.</div>
        @endforelse
    </section>
    @endif
</x-layouts.public>
