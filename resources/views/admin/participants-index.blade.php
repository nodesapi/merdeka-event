<x-layouts.app title="Peserta" header="Peserta per Kategori Umur">
    @if ($competitions->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">
            Belum ada lomba. Tambahkan lewat menu <a href="{{ route('admin.competitions') }}" class="font-semibold text-red-600 hover:underline">Lomba</a> terlebih dahulu.
        </div>
    @else
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Pilih Lomba</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($competitions as $c)
                    @php $isActive = $selected && $selected->id === $c->id; @endphp
                    <a href="{{ route('admin.participants-index', ['lomba' => $c->slug]) }}"
                       class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition {{ $isActive ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <span>{{ $c->name }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $isActive ? 'bg-white/20 text-white' : 'bg-white text-slate-500' }}">{{ $c->participants_count }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if ($selected)
            <livewire:participant-manager :competition="$selected" :key="$selected->id" />
        @endif
    @endif
</x-layouts.app>
