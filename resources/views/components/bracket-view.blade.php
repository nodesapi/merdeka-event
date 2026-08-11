@props(['matchesByRound', 'entrantsById', 'linesPerMatch', 'interactive' => false])

@php
    use App\Support\BracketTreeLayout;

    $rounds = $matchesByRound->keys()->sort()->values();
    $isTree = (int) $linesPerMatch === 2;

    $leftRounds = collect();
    $rightRounds = collect();
    $finalMatch = null;
    $thirdPlaceMatch = null;

    if ($isTree) {
        foreach ($rounds as $round) {
            $heats = $matchesByRound->get($round)->where('is_third_place', false)->values();
            $thirdPlaceMatch = $matchesByRound->get($round)->firstWhere('is_third_place', true) ?? $thirdPlaceMatch;

            if ($heats->count() === 1) {
                $finalMatch = $heats->first();
            } else {
                $half = intdiv($heats->count(), 2);
                $leftRounds->put($round, $heats->slice(0, $half)->values());
                $rightRounds->put($round, $heats->slice($half)->values());
            }
        }
    }

    $leftLayout = $isTree ? BracketTreeLayout::build($leftRounds, false) : null;
    $rightLayout = $isTree ? BracketTreeLayout::build($rightRounds, true) : null;

    // Peta kolom -> nomor babak, buat label "Babak N" di baris paling atas grid.
    $leftColumnRounds = $isTree ? $leftRounds->keys()->sort()->values() : collect();
    $rightColumnRounds = $isTree ? $rightRounds->keys()->sort()->values()->reverse()->values() : collect();
@endphp

@if ($isTree && $leftLayout['items'])
    <div class="overflow-x-auto pb-6">
        <div class="flex min-w-full items-center justify-center gap-6">
            {{-- Sisi kiri --}}
            <div class="grid gap-y-2" style="grid-template-columns: repeat({{ $leftLayout['cols'] * 2 - 1 }}, minmax(0, max-content)); grid-template-rows: 2rem repeat({{ $leftLayout['rows'] }}, minmax(1.5rem, auto));">
                @foreach ($leftColumnRounds as $index => $round)
                    <p class="self-end pb-1 text-center text-xs font-bold uppercase tracking-widest text-slate-400" style="grid-column: {{ ($index + 1) * 2 - 1 }}; grid-row: 1;">Babak {{ $round }}</p>
                @endforeach
                @foreach ($leftLayout['items'] as $item)
                    <div style="grid-column: {{ $item['col'] * 2 - 1 }}; grid-row: {{ $item['rowStart'] + 1 }} / span {{ $item['rowSpan'] }};" class="flex w-36 items-center {{ $item['rowSpan'] === $leftLayout['rows'] ? 'bracket-tip bracket-tip--right' : '' }}">
                        <x-bracket-match-box :match="$item['match']" :entrants-by-id="$entrantsById" :interactive="$interactive" />
                    </div>
                @endforeach
                @foreach ($leftLayout['connectors'] as $c)
                    <div class="bracket-connector bracket-connector--right" style="grid-column: {{ $c['col'] * 2 }}; grid-row: {{ $c['rowStart'] + 1 }} / span {{ $c['rowSpan'] }};"></div>
                @endforeach
            </div>

            {{-- Final --}}
            @if ($finalMatch)
                <div class="flex flex-col items-center gap-4">
                    <div>
                        <p class="mb-2 text-center text-xs font-bold uppercase tracking-widest text-slate-400">Final</p>
                        <div class="bracket-tip bracket-tip--left bracket-tip--right w-36">
                            <x-bracket-match-box :match="$finalMatch" :entrants-by-id="$entrantsById" :interactive="$interactive" />
                        </div>
                    </div>
                    @if ($thirdPlaceMatch)
                        <div>
                            <p class="mb-2 text-center text-xs font-bold uppercase tracking-widest text-slate-400">Juara 3</p>
                            <div class="w-36">
                                <x-bracket-match-box :match="$thirdPlaceMatch" :entrants-by-id="$entrantsById" :interactive="$interactive" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Sisi kanan --}}
            <div class="grid gap-y-2" style="grid-template-columns: repeat({{ $rightLayout['cols'] * 2 - 1 }}, minmax(0, max-content)); grid-template-rows: 2rem repeat({{ $rightLayout['rows'] }}, minmax(1.5rem, auto));">
                @foreach ($rightColumnRounds as $index => $round)
                    <p class="self-end pb-1 text-center text-xs font-bold uppercase tracking-widest text-slate-400" style="grid-column: {{ ($index + 1) * 2 - 1 }}; grid-row: 1;">Babak {{ $round }}</p>
                @endforeach
                @foreach ($rightLayout['items'] as $item)
                    <div style="grid-column: {{ $item['col'] * 2 - 1 }}; grid-row: {{ $item['rowStart'] + 1 }} / span {{ $item['rowSpan'] }};" class="flex w-36 items-center {{ $item['rowSpan'] === $rightLayout['rows'] ? 'bracket-tip bracket-tip--left' : '' }}">
                        <x-bracket-match-box :match="$item['match']" :entrants-by-id="$entrantsById" :interactive="$interactive" />
                    </div>
                @endforeach
                @foreach ($rightLayout['connectors'] as $c)
                    <div class="bracket-connector bracket-connector--left" style="grid-column: {{ $c['col'] * 2 }}; grid-row: {{ $c['rowStart'] + 1 }} / span {{ $c['rowSpan'] }};"></div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .bracket-connector {
            position: relative;
            width: 1.5rem;
        }
        .bracket-connector::before,
        .bracket-connector::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 0;
            border-top: 2px solid #cbd5e1;
        }
        .bracket-connector::before { top: 0; }
        .bracket-connector::after { bottom: 0; }
        .bracket-connector--right { border-right: 2px solid #cbd5e1; }
        .bracket-connector--left { border-left: 2px solid #cbd5e1; }

        /* Stub keluar dari babak terakhir tiap sisi menuju kotak Final. */
        .bracket-tip { position: relative; }
        .bracket-tip--right::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -1.5rem;
            width: 1.5rem;
            height: 0;
            border-top: 2px solid #cbd5e1;
        }
        .bracket-tip--left::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -1.5rem;
            width: 1.5rem;
            height: 0;
            border-top: 2px solid #cbd5e1;
        }
    </style>
@else
    <div class="overflow-x-auto pb-6">
        <div class="flex min-w-full justify-center">
            <div class="flex items-stretch gap-12">
                @foreach ($rounds as $round)
                    @php
                        $roundMatches = $matchesByRound->get($round);
                        $isFinalRound = $isTree && $round === $rounds->last() && $roundMatches->count() === 1;
                    @endphp
                    <x-bracket-column :heats="$roundMatches" :label="$isFinalRound ? 'Final' : 'Babak ' . $round" :entrants-by-id="$entrantsById" :interactive="$interactive" :is-tree="$isTree" />
                @endforeach
            </div>
        </div>
    </div>
@endif
