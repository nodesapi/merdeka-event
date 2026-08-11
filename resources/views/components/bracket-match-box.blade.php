@props(['match', 'entrantsById', 'interactive' => false])

@php
    $entrants = $match->resolveEntrants($entrantsById);
    $decided = $match->isDecided();
    $isBye = $entrants->count() === 1;
@endphp

<div class="w-full rounded-md border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="divide-y divide-slate-100">
        @forelse ($entrants as $entrant)
            @php $isWinner = $decided && $match->winner_entrant_id === $entrant->id; @endphp
            @if ($interactive && ! $decided && ! $isBye)
                <button type="button" wire:click="decideWinner('{{ $match->id }}', '{{ $entrant->id }}')" class="flex w-full items-center justify-between gap-1.5 px-2 py-1 text-left text-xs hover:bg-red-50">
                    <span class="min-w-0 truncate font-medium text-slate-800">{{ $entrant->display_name }}</span>
                    @if ($entrant->bracket_meta)
                        <span class="shrink-0 text-[10px] text-slate-400">{{ $entrant->bracket_meta }}</span>
                    @endif
                </button>
            @else
                <div class="flex items-center justify-between gap-1.5 px-2 py-1 text-xs {{ $isWinner ? 'bg-emerald-50' : ($decided ? 'opacity-50' : '') }}">
                    <span class="flex min-w-0 items-center gap-1">
                        @if ($isWinner)
                            <x-icon name="trophy" class="h-3 w-3 shrink-0 text-amber-500" />
                        @endif
                        <span class="truncate {{ $isWinner ? 'font-bold text-emerald-800' : 'font-medium text-slate-800' }} {{ ! $isWinner && $decided ? 'line-through' : '' }}">{{ $entrant->display_name }}</span>
                    </span>
                    @if ($entrant->bracket_meta)
                        <span class="shrink-0 text-[10px] text-slate-400">{{ $entrant->bracket_meta }}</span>
                    @endif
                </div>
            @endif
        @empty
            <div class="px-2 py-2 text-center text-xs text-slate-300">Menunggu…</div>
        @endforelse
        @if ($isBye && $decided)
            <p class="px-2 pb-1 text-[10px] text-slate-400">Bye — otomatis lolos</p>
        @endif
    </div>
    @if ($interactive && $decided && ! $isBye)
        <div class="border-t border-slate-100 px-2 py-1 text-right">
            <button type="button" wire:click="undoMatch('{{ $match->id }}')" class="text-[10px] font-medium text-slate-400 hover:text-red-600">Batalkan</button>
        </div>
    @endif
</div>
