@props(['heats', 'label' => null, 'entrantsById', 'interactive' => false, 'isTree' => false])

<div class="flex w-56 shrink-0 flex-col gap-4">
    @if ($label)
        <p class="mb-1 text-center text-xs font-bold uppercase tracking-widest text-slate-400">{{ $label }}</p>
    @endif
    @foreach ($heats as $match)
        <div>
            @unless ($isTree)
                <p class="mb-1 px-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Heat {{ $match->heat_number }}</p>
            @endunless
            <x-bracket-match-box :match="$match" :entrants-by-id="$entrantsById" :interactive="$interactive" />
        </div>
    @endforeach
</div>
