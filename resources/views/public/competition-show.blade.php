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
    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="merdeka-badge">Detail Lomba</span>
            <h1 class="mt-3 flex items-center gap-3 text-2xl font-black tracking-tight text-stone-900">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-700"><x-icon name="trophy" class="h-6 w-6" /></span>
                {{ $competition->name }}
            </h1>
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $competition->target_participants }}</p>
            @if ($competition->description)
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-500">{{ $competition->description }}</p>
            @endif
        </div>
        <div class="flex shrink-0 gap-3">
            <div class="merdeka-card px-4 py-3 text-center">
                <p class="text-2xl font-extrabold text-stone-900">{{ $competition->participants->count() }}</p>
                <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Peserta</p>
            </div>
            <div class="merdeka-card px-4 py-3 text-center">
                <p class="text-2xl font-extrabold text-stone-900">{{ $competition->total_rounds }}</p>
                <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Babak</p>
            </div>
        </div>
    </div>

    {{-- Winners --}}
    @if ($winners->isNotEmpty())
        <section class="mt-6">
            <h2 class="flex items-center gap-2 text-lg font-extrabold tracking-tight text-stone-900"><x-icon name="trophy" class="h-5 w-5 text-amber-500" /> Papan Juara</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach ($winners as $winner)
                    <article class="rounded-xl border p-4 text-center shadow-sm {{ $rankStyles[$winner->rank] ?? 'border-stone-200 bg-white text-stone-700' }}">
                        <x-icon name="medal" class="mx-auto h-9 w-9 {{ $rankColor[$winner->rank] ?? 'text-stone-400' }}" />
                        <p class="mt-1 text-xs font-bold uppercase tracking-wide">{{ $rankLabels[$winner->rank] ?? ('Peringkat ' . $winner->rank) }}</p>
                        <p class="mt-1 text-base font-extrabold text-stone-900">{{ $winner->name }}</p>
                        <p class="text-sm text-stone-500">{{ $winner->resident_block }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Participants per round --}}
    <section class="mt-8">
        <h2 class="text-lg font-extrabold tracking-tight text-stone-900">Peserta per Babak</h2>
        <p class="mt-1 text-sm text-stone-500">Peserta yang lolos naik ke babak berikutnya. Data diperbarui oleh panitia.</p>

        @forelse ($participantsByRound as $round => $participants)
            <div class="mt-5">
                <div class="flex items-center gap-2.5">
                    <h3 class="font-extrabold text-stone-900">Babak {{ $round }}</h3>
                    <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-[11px] font-bold text-stone-600">{{ $participants->count() }} peserta</span>
                </div>
                <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($participants as $participant)
                        <article class="flex items-center justify-between gap-3 rounded-lg border border-stone-200 bg-white p-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-stone-900">{{ $participant->name }}</p>
                                <p class="text-sm text-stone-400">{{ $participant->resident_block ?: '-' }}</p>
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
            <p class="mt-4 text-sm text-stone-500">Belum ada peserta yang terdaftar untuk lomba ini.</p>
        @endforelse
    </section>
</x-layouts.public>
