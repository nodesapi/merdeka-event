@props(['match', 'entrantsById', 'interactive' => false, 'compact' => true])

@php
    $entrants = $match->resolveEntrants($entrantsById);
    $decided = $match->isDecided();
    $isBye = $entrants->count() === 1;
    $isRanked = $match->is_third_place || $match->isFinalMatch();
    $hasAnyPlacement = $match->hasAnyPlacement();
    $placedCount = $match->entrants->filter(fn ($e) => $e->placement !== null)->count();
    $canFinalizeEarly = $interactive && ! $decided && ! $isRanked && ! $isBye && $placedCount >= 1;

    $rowPad = $compact ? 'px-2 py-1' : 'px-4 py-3';
    $textSize = $compact ? 'text-xs' : 'text-base';
    $metaSize = $compact ? 'text-[10px]' : 'text-sm';
    $iconSize = $compact ? 'h-3 w-3' : 'h-5 w-5';
    $gap = $compact ? 'gap-1.5' : 'gap-2.5';

    $medalLabel = [1 => 'Juara 1', 2 => 'Juara 2', 3 => 'Juara 3'];
    $medalColor = [1 => 'text-amber-500', 2 => 'text-slate-400', 3 => 'text-amber-700'];
@endphp

<div class="w-full rounded-md border border-slate-200 bg-white shadow-sm overflow-hidden {{ $compact ? '' : 'shadow' }}">
    <div class="divide-y divide-slate-100">
        @forelse ($entrants as $entrant)
            @php $placement = $match->placementFor($entrant->id); @endphp
            @if ($interactive && ! $decided && $placement === null && ! $isBye)
                <button type="button" wire:click="recordFinish('{{ $match->id }}', '{{ $entrant->id }}')" class="flex w-full items-center justify-between {{ $gap }} {{ $rowPad }} text-left {{ $textSize }} hover:bg-red-50 active:bg-red-100">
                    <span class="min-w-0 truncate font-medium text-slate-800">{{ $entrant->display_name }}</span>
                    @if ($entrant->bracket_meta)
                        <span class="shrink-0 {{ $metaSize }} text-slate-400">{{ $entrant->bracket_meta }}</span>
                    @endif
                </button>
            @else
                @php $isPlaced = $placement !== null; @endphp
                <div class="flex items-center justify-between {{ $gap }} {{ $rowPad }} {{ $textSize }} {{ $isPlaced ? 'bg-emerald-50' : ($decided ? 'opacity-50' : '') }}">
                    <span class="flex min-w-0 items-center gap-1.5">
                        @if ($isPlaced && $isRanked && $placement <= 3)
                            <x-icon name="{{ $placement === 1 ? 'trophy' : 'medal' }}" class="{{ $iconSize }} shrink-0 {{ $medalColor[$placement] }}" />
                        @endif
                        <span class="truncate {{ $isPlaced ? 'font-bold text-emerald-800' : 'font-medium text-slate-800' }} {{ ! $isPlaced && $decided ? 'line-through' : '' }}">{{ $entrant->display_name }}</span>
                        @if ($isPlaced)
                            <span class="shrink-0 {{ $metaSize }} font-bold uppercase tracking-wide {{ $isRanked ? ($medalColor[$placement] ?? 'text-emerald-600') : 'text-emerald-600' }}">
                                {{ $isRanked ? ($medalLabel[$placement] ?? 'Peringkat ' . $placement) : 'Lolos' }}
                            </span>
                        @endif
                    </span>
                    @if ($entrant->bracket_meta)
                        <span class="shrink-0 {{ $metaSize }} text-slate-400">{{ $entrant->bracket_meta }}</span>
                    @endif
                </div>
            @endif
        @empty
            <div class="px-2 py-2 text-center {{ $textSize }} text-slate-300">Menunggu…</div>
        @endforelse
        @if ($isBye && $decided)
            <p class="px-2 pb-1 {{ $metaSize }} text-slate-400">Bye — otomatis lolos</p>
        @endif
    </div>
    @if ($interactive && $hasAnyPlacement && ! $isBye)
        <div class="border-t border-slate-100 {{ $compact ? 'px-2 py-1' : 'px-4 py-2' }} flex items-center justify-between gap-2">
            @if ($canFinalizeEarly)
                <button type="button" wire:click="finalizeHeatEarly('{{ $match->id }}')" class="{{ $compact ? 'text-[10px]' : 'text-sm' }} font-semibold text-emerald-600 hover:text-emerald-800">Selesaikan Heat ({{ $placedCount }} lolos)</button>
            @else
                <span></span>
            @endif
            <button type="button" wire:click="undoMatch('{{ $match->id }}')" class="{{ $compact ? 'text-[10px]' : 'text-sm' }} font-medium text-slate-400 hover:text-red-600">Batalkan</button>
        </div>
    @endif
</div>
