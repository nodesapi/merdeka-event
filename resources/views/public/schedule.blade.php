<x-layouts.public title="Susunan Acara" :eventName="$event?->name">
    <div class="mx-auto max-w-2xl text-left">
        <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
            <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-center">
                <span class="hidden h-9 w-1.5 rounded-full bg-red-600 sm:block"></span>
                <div>
                    <span class="merdeka-badge">Susunan Acara</span>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Rundown Kegiatan</h1>
                    <p class="mt-1 text-sm leading-6 text-stone-500">Jadwal rangkaian acara {{ $event?->name ?? 'HUT RI' }}.</p>
                </div>
            </div>
            @if ($event?->schedule_label)
                <span class="shrink-0 rounded-full bg-red-50 px-3.5 py-1.5 text-sm font-semibold text-red-700">{{ $event->schedule_label }}</span>
            @endif
        </div>

        @if ($schedules->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center text-sm text-stone-500">
                Belum ada susunan acara yang ditambahkan.
            </div>
        @else
            <div class="mt-8 space-y-0">
                @foreach ($schedules as $index => $item)
                    <div class="relative flex gap-4 pb-8 last:pb-0">
                        @if (! $loop->last)
                            <span class="absolute left-[15px] top-8 h-full w-px bg-stone-200"></span>
                        @endif
                        <span class="relative z-10 mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-red-700 text-xs font-black text-white shadow-md shadow-red-600/20">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">
                                <x-icon name="clock" class="h-3.5 w-3.5" /> {{ $item->time_label }}
                            </span>
                            <p class="mt-2 font-black text-stone-900">{{ $item->activity }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
