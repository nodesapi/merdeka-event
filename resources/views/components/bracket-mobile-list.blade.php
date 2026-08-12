@props(['matchesByRound', 'entrantsById', 'interactive' => false])

@php
    $rounds = $matchesByRound->keys()->sort()->values();
    $latestRound = $rounds->last();
@endphp

<div class="space-y-3">
    @foreach ($rounds->reverse() as $round)
        @php
            $heats = $matchesByRound->get($round);
            $mainHeats = $heats->where('is_third_place', false);
            $isFinalRound = $round === $latestRound && $mainHeats->count() === 1;
        @endphp

        @if ($round === $latestRound)
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-slate-400">{{ $isFinalRound ? 'Final' : 'Babak ' . $round }}</p>
                <div class="space-y-3">
                    @foreach ($heats as $match)
                        <div>
                            @if ($match->is_third_place)
                                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-amber-600">Juara 3</p>
                            @elseif (! $isFinalRound && $heats->count() > 1)
                                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Heat {{ $match->heat_number }}</p>
                            @endif
                            <x-bracket-match-box :match="$match" :entrants-by-id="$entrantsById" :interactive="$interactive" :compact="false" />
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <details class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <summary class="cursor-pointer select-none text-xs font-semibold text-slate-500">Babak {{ $round }} <span class="font-normal text-slate-400">({{ $heats->count() }} heat, selesai)</span></summary>
                <div class="mt-2 space-y-1 pl-1">
                    @foreach ($heats as $match)
                        @php $winner = $match->winnerEntrant($entrantsById); @endphp
                        <p class="text-xs text-slate-600">
                            @if ($match->is_third_place)
                                <span class="font-semibold text-amber-600">Juara 3:</span>
                            @else
                                <span class="text-slate-400">Heat {{ $match->heat_number }}:</span>
                            @endif
                            {{ $winner?->display_name ?? 'Belum ada pemenang' }}
                        </p>
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach
</div>
