<?php

use App\Models\Event;
use App\Models\RabFundingSource;
use Livewire\Component;

new class extends Component
{
    public ?string $editingId = null;

    public string $kategori = '';
    public string $sumber = '';
    public $target = '';
    public $realisasi = 0;
    public string $catatan = '';

    public string $success_message = '';

    /** Contoh kategori sumber dana — bukan daftar tertutup, panitia bebas mengetik kategori lain. "Iuran" tidak termasuk karena dihitung otomatis. */
    public array $categorySuggestions = ['Sponsor', 'Donasi', 'Lainnya'];

    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    protected function rules(): array
    {
        return [
            'kategori' => 'required|string|max:100',
            'sumber' => 'required|string|max:255',
            'target' => 'required|numeric|min:0',
            'realisasi' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if (strtolower(trim($data['kategori'])) === 'iuran') {
            $this->addError('kategori', 'Kategori "Iuran" sudah dihitung otomatis dari Nominal Iuran x Target Rumah (Acara & Jadwal) dan transaksi Form Warga yang terverifikasi — tidak perlu ditambahkan manual.');
            return;
        }

        $data['catatan'] = $data['catatan'] ?: null;
        $data['realisasi'] = $data['realisasi'] ?: 0;

        if ($this->editingId) {
            RabFundingSource::where('id', $this->editingId)->update($data);
            $this->success_message = 'Sumber dana "' . $this->sumber . '" berhasil diperbarui!';
        } else {
            RabFundingSource::create($data);
            $this->success_message = 'Sumber dana "' . $this->sumber . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $item = RabFundingSource::findOrFail($id);
        $this->editingId = $item->id;
        $this->kategori = $item->kategori;
        $this->sumber = $item->sumber;
        $this->target = (string) $item->target;
        $this->realisasi = (string) $item->realisasi;
        $this->catatan = $item->catatan ?? '';
    }

    public function delete(string $id): void
    {
        RabFundingSource::where('id', $id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Sumber dana berhasil dihapus.';
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'kategori', 'sumber', 'target', 'realisasi', 'catatan']);
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $event = $this->activeEvent();
        $iuranTarget = (float) ($event?->contribution_target_amount ?? 0);
        $iuranRealisasi = (float) ($event?->iuran_realisasi ?? 0);

        $manualTarget = (float) RabFundingSource::sum('target');
        $manualRealisasi = (float) RabFundingSource::sum('realisasi');

        return [
            'event' => $event,
            'items' => RabFundingSource::orderBy('kategori')->orderBy('sumber')->get(),
            'iuranTarget' => $iuranTarget,
            'iuranRealisasi' => $iuranRealisasi,
            'totalTarget' => $iuranTarget + $manualTarget,
            'totalRealisasi' => $iuranRealisasi + $manualRealisasi,
        ];
    }
};
?>

<div>
    @if ($success_message)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
            <span class="text-sm font-medium">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="text-lg font-bold leading-none text-emerald-500 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    @php
        $selisihDana = $totalTarget - $totalRealisasi;
    @endphp

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Target Dana</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">Rp{{ number_format($totalTarget, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Realisasi Dana</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">Rp{{ number_format($totalRealisasi, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border p-5 shadow-sm {{ $selisihDana <= 0 ? 'border-emerald-600 bg-emerald-600' : 'border-amber-500 bg-amber-500' }} text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/80">{{ $selisihDana <= 0 ? 'Target Tercapai' : 'Kurang dari Target' }}</p>
            <p class="mt-2 text-3xl font-extrabold">Rp{{ number_format(abs($selisihDana), 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)]">
        {{-- Form --}}
        <div class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-emerald-600"></span> {{ $editingId ? 'Ubah Sumber Dana' : 'Tambah Sumber Dana' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Kategori</label>
                    <input type="text" wire:model="kategori" list="funding-category-suggestions" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="Contoh: Sponsor">
                    <datalist id="funding-category-suggestions">
                        @foreach ($categorySuggestions as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-slate-400">Kategori "Iuran" sudah otomatis, tidak perlu diinput di sini.</p>
                    @error('kategori') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Nama Sumber</label>
                    <input type="text" wire:model="sumber" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="Contoh: Widari">
                    @error('sumber') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Target (Rp)</label>
                    <div class="relative" data-rupiah-input>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                        <input type="text" value="{{ $target }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-md border border-slate-300 py-2.5 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="1.000.000">
                        <input type="hidden" wire:model="target" value="{{ $target }}" data-rupiah-hidden>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Perkiraan/target dana dari sumber ini.</p>
                    @error('target') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Realisasi (Rp) <span class="font-normal text-slate-400">(opsional)</span></label>
                    <div class="relative" data-rupiah-input>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                        <input type="text" value="{{ $realisasi }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-md border border-slate-300 py-2.5 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="0">
                        <input type="hidden" wire:model="realisasi" value="{{ $realisasi }}" data-rupiah-hidden>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Nominal yang sudah benar-benar diterima dari sumber ini.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea wire:model="catatan" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="Info tambahan"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="rounded-md border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Sumber' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-slate-900">Daftar Sumber Dana</h3>
            </div>
            <div class="p-4 sm:p-5">
                {{-- Iuran — dihitung otomatis, tidak lewat form manual --}}
                <div class="mb-6">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                            Iuran
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold normal-case text-slate-500">Otomatis</span>
                        </h4>
                        <p class="text-xs text-slate-400">
                            Target Rp{{ number_format($iuranTarget, 0, ',', '.') }}
                            &middot; Realisasi Rp{{ number_format($iuranRealisasi, 0, ',', '.') }}
                        </p>
                    </div>
                    @if ($event && $event->recommended_contribution_amount && $event->contribution_target_households)
                        <div class="overflow-x-auto rounded-md border border-slate-100">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Sumber</th>
                                        <th class="px-3 py-2 text-right font-semibold">Target</th>
                                        <th class="px-3 py-2 text-right font-semibold">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-slate-100 bg-white">
                                        <td class="px-3 py-2.5">
                                            <p class="font-semibold text-slate-900">Total Iuran Warga</p>
                                            <p class="text-xs text-slate-400">Rp{{ number_format($event->recommended_contribution_amount, 0, ',', '.') }}/rumah &times; {{ number_format($event->contribution_target_households, 0, ',', '.') }} rumah</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-slate-700">Rp{{ number_format($iuranTarget, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold text-emerald-700">Rp{{ number_format($iuranRealisasi, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Realisasi dihitung realtime dari transaksi Form Warga (iuran) yang sudah terverifikasi. Untuk ubah nominal/target iuran, atur lewat <a href="{{ route('admin.event') }}" class="font-semibold text-emerald-700 hover:underline">Acara &amp; Jadwal</a>.</p>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">
                                Nominal iuran dan/atau target jumlah rumah belum diatur.
                                Atur lewat <a href="{{ route('admin.event') }}" class="font-semibold text-emerald-700 hover:underline">Acara &amp; Jadwal</a> untuk menampilkan target &amp; realisasi iuran di sini.
                            </p>
                        </div>
                    @endif
                </div>

                @if ($items->isEmpty())
                    <p class="py-6 text-center text-sm text-slate-400">Belum ada sumber dana lain (Sponsor/Donasi). Tambahkan lewat form di samping.</p>
                @else
                    @php
                        $groupedItems = $items->groupBy('kategori');
                    @endphp
                    @foreach ($groupedItems as $kategori => $groupItems)
                        <div wire:key="funding-group-{{ $kategori }}" class="mb-6 last:mb-0">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $kategori }}</h4>
                                <p class="text-xs text-slate-400">
                                    Target Rp{{ number_format($groupItems->sum('target'), 0, ',', '.') }}
                                    &middot; Realisasi Rp{{ number_format($groupItems->sum('realisasi'), 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-slate-100">
                                <table class="w-full border-collapse text-left text-sm">
                                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2 font-semibold">Sumber</th>
                                            <th class="px-3 py-2 text-right font-semibold">Target</th>
                                            <th class="px-3 py-2 text-right font-semibold">Realisasi</th>
                                            <th class="px-3 py-2 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupItems as $item)
                                            <tr wire:key="funding-{{ $item->id }}" class="border-b border-slate-100 odd:bg-white even:bg-slate-50/60 hover:bg-emerald-50/30">
                                                <td class="px-3 py-2.5">
                                                    <p class="font-semibold text-slate-900">{{ $item->sumber }}</p>
                                                    @if ($item->catatan)
                                                        <p class="text-xs text-slate-400">{{ $item->catatan }}</p>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-slate-700">Rp{{ number_format($item->target, 0, ',', '.') }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold text-emerald-700">Rp{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2.5 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <button wire:click="edit('{{ $item->id }}')" class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Ubah</button>
                                                        <button wire:click="delete('{{ $item->id }}')" wire:confirm="Hapus sumber dana ini?" class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
