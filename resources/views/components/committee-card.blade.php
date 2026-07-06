@props(['member', 'variant' => 'default'])

@php
    $isLead = $variant === 'lead';
    $isCore = $variant === 'core';

    $accent = match (true) {
        $isLead => 'border-l-amber-400',
        $isCore => 'border-l-red-500',
        default => 'border-l-stone-300',
    };
    $ring = match (true) {
        $isLead => 'ring-2 ring-amber-300',
        $isCore => 'ring-2 ring-red-200',
        default => 'ring-1 ring-stone-200',
    };

    $pos = strtolower($member->position ?? '');
    $isChair = str_contains($pos, 'ketua') && ! str_contains($pos, 'wakil');

    $digits = preg_replace('/\D+/', '', $member->phone_number ?? '');
    $wa = $digits !== '' ? 'https://wa.me/' . (str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits) : null;
@endphp

<article {{ $attributes->merge(['class' => "flex items-center gap-3 rounded-xl border border-stone-200 border-l-4 $accent bg-white p-3 shadow-sm transition hover:border-red-200 hover:shadow"]) }}>
    @if ($member->photo_url)
        <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="h-11 w-11 shrink-0 {{ $ring }} rounded-full object-cover">
    @else
        <div class="flex h-11 w-11 shrink-0 items-center justify-center {{ $ring }} rounded-full bg-gradient-to-br from-red-600 to-red-800 text-base font-black text-white">
            {{ strtoupper(substr($member->name, 0, 1)) }}
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <p class="flex items-center gap-1.5 text-sm font-black text-stone-900">
            <span class="truncate">{{ $member->name }}</span>@if ($isChair)<x-icon name="crown" class="h-4 w-4 shrink-0 text-amber-500" />@endif
        </p>
        <p class="truncate text-xs font-bold uppercase tracking-wide text-red-700">{{ $member->position }}</p>
        <p class="mt-0.5 truncate text-[11px] text-stone-400">Blok {{ $member->resident_block ?: '-' }}</p>
    </div>

    @if ($wa)
        <a href="{{ $wa }}" target="_blank" title="WhatsApp {{ $member->phone_number }}"
           class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition hover:bg-emerald-100">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.548 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
    @endif
</article>
