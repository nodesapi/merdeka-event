<?php

use App\Models\Event;
use App\Models\RabFundingSource;
use App\Models\RabItem;
use App\Traits\ConfirmsDeletion;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithFileUploads, ConfirmsDeletion;

    public ?string $editingId = null;

    public string $kategori = '';
    public string $nama_item = '';
    public $volume = 1;
    public string $satuan = '';
    public $harga_satuan = '';
    public $realisasi = 0;
    public string $pj = '';
    public string $status = 'belum';
    public string $catatan = '';

    public string $filter = '';
    public string $search = '';

    public string $success_message = '';

    public $rabImportFile = null;
    public array $importErrors = [];

    /** Contoh kategori acara 17-an — bukan daftar tertutup, panitia bebas mengetik kategori lain. */
    public array $categorySuggestions = [
        'Kesekretariatan & Administrasi', 'Perlengkapan & Dekorasi', 'Konsumsi',
        'Lomba & Hadiah', 'Sound System & Hiburan', 'Bazaar',
        'Keamanan & Kesehatan', 'Dokumentasi', 'Transportasi & Logistik',
        'Kebersihan', 'Sosial', 'Dana Cadangan/Tak Terduga',
    ];

    protected function rules(): array
    {
        return [
            'kategori' => 'required|string|max:100',
            'nama_item' => 'required|string|max:255',
            'volume' => 'required|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'realisasi' => 'nullable|numeric|min:0',
            'pj' => 'nullable|string|max:100',
            'status' => 'required|in:belum,proses,selesai',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['satuan'] = $data['satuan'] ?: null;
        $data['pj'] = $data['pj'] ?: null;
        $data['catatan'] = $data['catatan'] ?: null;
        $data['realisasi'] = $data['realisasi'] ?: 0;
        // Jumlah Rencana selalu dihitung sistem dari Volume x Harga Satuan — tidak bisa diisi manual, biar tidak ada human error.
        $data['jumlah_rencana'] = round(((float) $data['volume']) * ((float) $data['harga_satuan']));

        if ($this->editingId) {
            RabItem::where('id', $this->editingId)->update($data);
            $this->success_message = 'Item RAB "' . $this->nama_item . '" berhasil diperbarui!';
        } else {
            RabItem::create($data);
            $this->success_message = 'Item RAB "' . $this->nama_item . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $item = RabItem::findOrFail($id);
        $this->editingId = $item->id;
        $this->kategori = $item->kategori;
        $this->nama_item = $item->nama_item;
        $this->volume = (string) (float) $item->volume;
        $this->satuan = $item->satuan ?? '';
        $this->harga_satuan = (string) (float) $item->harga_satuan;
        $this->realisasi = (string) (float) $item->realisasi;
        $this->pj = $item->pj ?? '';
        $this->status = $item->status;
        $this->catatan = $item->catatan ?? '';
    }

    public function delete(string $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang boleh menghapus item RAB.');

        RabItem::where('id', $id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Item RAB berhasil dihapus.';
    }

    /**
     * Upload CSV hasil export (kolom: ID, Kategori, Nama Item, Volume, Satuan, Harga Satuan,
     * Jumlah Rencana, Realisasi, Selisih, PJ, Status, Catatan). Baris dengan ID yang cocok
     * akan diperbarui, baris tanpa ID/tidak cocok akan ditambahkan baru. Bersifat upsert-only
     * — tidak pernah menghapus baris yang hilang dari file.
     */
    public function importRab(): void
    {
        $this->importErrors = [];

        $this->validate([
            'rabImportFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($this->rabImportFile->getRealPath(), 'r');
        if (! $handle) {
            $this->addError('rabImportFile', 'File tidak bisa dibaca.');
            return;
        }

        // Lewati UTF-8 BOM kalau ada (file hasil export pakai BOM).
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        fgetcsv($handle); // lewati baris header

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNumber = 1;
        $validStatuses = ['belum', 'proses', 'selesai'];

        DB::transaction(function () use ($handle, &$created, &$updated, &$errors, &$rowNumber, $validStatuses) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                [$id, $kategori, $namaItem, $volume, $satuan, $hargaSatuan, $jumlahRencana, $realisasi, $selisih, $pj, $status, $catatan] = array_pad($row, 12, null);

                $kategori = trim((string) $kategori);
                $namaItem = trim((string) $namaItem);

                if ($kategori === '' || $namaItem === '') {
                    $errors[] = "Baris {$rowNumber}: Kategori/Nama Item kosong, dilewati.";
                    continue;
                }

                $status = trim((string) $status);
                if (! in_array($status, $validStatuses, true)) {
                    $errors[] = "Baris {$rowNumber}: status \"{$status}\" tidak dikenal, diset ke \"belum\".";
                    $status = 'belum';
                }

                $data = [
                    'kategori' => $kategori,
                    'nama_item' => $namaItem,
                    'volume' => is_numeric($volume) ? (float) $volume : 1,
                    'satuan' => trim((string) $satuan) ?: null,
                    'harga_satuan' => is_numeric($hargaSatuan) ? (float) $hargaSatuan : 0,
                    'jumlah_rencana' => is_numeric($jumlahRencana) ? (float) $jumlahRencana : 0,
                    'realisasi' => is_numeric($realisasi) ? (float) $realisasi : 0,
                    'pj' => trim((string) $pj) ?: null,
                    'status' => $status,
                    'catatan' => trim((string) $catatan) ?: null,
                ];

                $id = trim((string) $id);
                $existing = $id !== '' ? RabItem::where('id', $id)->first() : null;

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    RabItem::create($data);
                    $created++;
                }
            }
        });

        fclose($handle);

        $this->importErrors = $errors;
        $this->success_message = "Import selesai: {$created} item ditambahkan, {$updated} diperbarui."
            . (count($errors) ? ' ' . count($errors) . ' baris bermasalah, lihat detail di bawah.' : '');

        $this->reset(['rabImportFile']);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'kategori', 'nama_item', 'satuan', 'harga_satuan', 'realisasi', 'pj', 'catatan']);
        $this->volume = 1;
        $this->status = 'belum';
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $query = RabItem::query();

        if ($this->filter !== '') {
            $query->where('kategori', $this->filter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nama_item', 'like', $term)
                    ->orWhere('pj', 'like', $term)
                    ->orWhere('catatan', 'like', $term);
            });
        }

        $event = Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
        $iuranRealisasi = (float) ($event?->iuran_realisasi ?? 0);

        return [
            'items' => $query->orderBy('kategori')->orderBy('nama_item')->get(),
            'existingCategories' => RabItem::query()->distinct()->orderBy('kategori')->pluck('kategori'),
            'totalRencana' => (float) RabItem::sum('jumlah_rencana'),
            'totalRealisasi' => (float) RabItem::sum('realisasi'),
            'totalRealisasiDana' => $iuranRealisasi + (float) RabFundingSource::sum('realisasi'),
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
        $totalSelisih = $totalRencana - $totalRealisasi;
    @endphp

    {{-- Realisasi dana terkumpul (Sumber Dana) vs kebutuhan anggaran (RAB) --}}
    @php
        $selisihEstimasi = $totalRealisasiDana - $totalRencana;
    @endphp
    <div class="mb-6 flex flex-col gap-4 rounded-lg border p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between {{ $selisihEstimasi >= 0 ? 'border-emerald-600 bg-emerald-600' : 'border-red-600 bg-red-600' }} text-white">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/80">{{ $selisihEstimasi >= 0 ? 'Sudah Tercukupi' : 'Masih Kurang' }}</p>
            <p class="mt-1 text-sm text-white/90">Realisasi Dana Terkumpul (Sumber Dana) Rp{{ number_format($totalRealisasiDana, 0, ',', '.') }} dibanding Total Kebutuhan Anggaran / Target Dana (RAB) Rp{{ number_format($totalRencana, 0, ',', '.') }}</p>
        </div>
        <p class="text-3xl font-extrabold sm:text-4xl">Rp{{ number_format(abs($selisihEstimasi), 0, ',', '.') }}</p>
    </div>

    {{-- Ringkasan --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Total Rencana</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format($totalRencana, 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><x-icon name="wallet" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Total Realisasi</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format($totalRealisasi, 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><x-icon name="arrow-up-tray" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border p-5 text-white shadow-sm {{ $totalSelisih >= 0 ? 'border-emerald-600 bg-emerald-600' : 'border-red-600 bg-red-600' }}">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/80">Selisih</p>
                <p class="mt-2 text-2xl font-bold">Rp{{ number_format(abs($totalSelisih), 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white/20 text-white"><x-icon name="calendar" class="h-6 w-6" /></span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)]">
        {{-- Form --}}
        <div class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> {{ $editingId ? 'Ubah Item RAB' : 'Tambah Item RAB' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Kategori</label>
                    <input type="text" wire:model="kategori" list="rab-category-suggestions" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: Konsumsi">
                    <datalist id="rab-category-suggestions">
                        @foreach ($categorySuggestions as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    @error('kategori') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Nama Item / Uraian</label>
                    <input type="text" wire:model="nama_item" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: Sewa tenda 4x6">
                    @error('nama_item') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Volume</label>
                        <input type="number" step="0.01" wire:model.live="volume" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="1">
                        @error('volume') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Satuan <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="satuan" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="pcs / paket / hari">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Harga Satuan (Rp)</label>
                    <div class="relative" data-rupiah-input>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                        <input type="text" value="{{ $harga_satuan }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-md border border-slate-300 py-2.5 pl-12 pr-4 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="100.000">
                        <input type="hidden" wire:model.live="harga_satuan" value="{{ $harga_satuan }}" data-rupiah-hidden>
                    </div>
                    @error('harga_satuan') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Jumlah Rencana (Rp)</label>
                    <div class="w-full rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700">
                        Rp{{ number_format((is_numeric($volume) ? (float) $volume : 0) * (is_numeric($harga_satuan) ? (float) $harga_satuan : 0), 0, ',', '.') }}
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Otomatis dihitung sistem dari Volume × Harga Satuan.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Realisasi (Rp) <span class="font-normal text-slate-400">(opsional)</span></label>
                    <div class="relative" data-rupiah-input>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                        <input type="text" value="{{ $realisasi }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-md border border-slate-300 py-2.5 pl-12 pr-4 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="0">
                        <input type="hidden" wire:model="realisasi" value="{{ $realisasi }}" data-rupiah-hidden>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Nominal yang sudah benar-benar dikeluarkan untuk item ini.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">PJ <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="pj" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Nama penanggung jawab">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <select wire:model="status" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
                            <option value="belum">Belum Direalisasi</option>
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea wire:model="catatan" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Info vendor, alasan selisih, dll"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="rounded-md border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Item' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Datatable --}}
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select wire:model.live="filter" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:w-48">
                        <option value="">Semua Kategori</option>
                        @foreach ($existingCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:w-52" placeholder="Cari item / PJ / catatan...">
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.rab.export', ['format' => 'csv']) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <x-icon name="wallet" class="h-4 w-4" /> Excel
                    </a>
                    <a href="{{ route('admin.rab.export', ['format' => 'pdf']) }}" target="_blank" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        <x-icon name="calendar" class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>

            <div class="border-b border-slate-100 p-4 sm:p-5">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <p class="text-xs font-semibold text-slate-600">Import dari Excel</p>
                    <p class="mt-1 text-xs text-slate-400">Upload file CSV hasil edit (kolom ID jangan diubah/dihapus). Baris yang kolom ID-nya cocok akan diperbarui, baris baru tanpa ID akan ditambahkan. Baris yang dihapus di Excel <span class="font-semibold">tidak ikut terhapus</span> di sistem — hapus manual lewat tombol Hapus.</p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input type="file" wire:model="rabImportFile" accept=".csv,text/csv" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300">
                        <button type="button" wire:click="importRab" wire:loading.attr="disabled" wire:target="rabImportFile,importRab" class="shrink-0 rounded-md bg-slate-700 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                            <span wire:loading.remove wire:target="importRab">Import</span>
                            <span wire:loading wire:target="rabImportFile,importRab">Memproses...</span>
                        </button>
                    </div>
                    @error('rabImportFile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    @if (!empty($importErrors))
                        <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800">
                            <p class="font-semibold">Catatan import:</p>
                            <ul class="mt-1 list-disc pl-4">
                                @foreach ($importErrors as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-5">
                @if ($items->isEmpty())
                    <p class="py-10 text-center text-sm text-slate-400">Belum ada item RAB. Tambahkan lewat form di samping.</p>
                @else
                    @php
                        $groupedItems = $items->groupBy('kategori');
                    @endphp
                    @foreach ($groupedItems as $kategori => $groupItems)
                        <div wire:key="rab-group-{{ $kategori }}" class="mb-6 last:mb-0">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $kategori }}</h4>
                                <p class="text-xs text-slate-400">
                                    Rencana Rp{{ number_format($groupItems->sum('jumlah_rencana'), 0, ',', '.') }}
                                    &middot; Realisasi Rp{{ number_format($groupItems->sum('realisasi'), 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-slate-100">
                                <table class="w-full border-collapse text-left text-sm">
                                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2 font-semibold">Item</th>
                                            <th class="px-3 py-2 text-right font-semibold">Rencana</th>
                                            <th class="px-3 py-2 text-right font-semibold">Realisasi</th>
                                            <th class="px-3 py-2 text-right font-semibold">Selisih</th>
                                            <th class="px-3 py-2 font-semibold">Status</th>
                                            <th class="px-3 py-2 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupItems as $item)
                                            <tr wire:key="rab-item-{{ $item->id }}" class="border-b border-slate-100 odd:bg-white even:bg-slate-50/60 hover:bg-red-50/30">
                                                <td class="px-3 py-2.5">
                                                    <p class="font-semibold text-slate-900">{{ $item->nama_item }}</p>
                                                    <p class="text-xs text-slate-400">
                                                        {{ (float) $item->volume }}{{ $item->satuan ? ' ' . $item->satuan : '' }}
                                                        &middot; Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}/{{ $item->satuan ?: 'unit' }}
                                                        @if ($item->pj) &middot; {{ $item->pj }} @endif
                                                    </p>
                                                    @if ($item->catatan)
                                                        <p class="mt-0.5 text-xs italic text-slate-400">{{ $item->catatan }}</p>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-slate-700">Rp{{ number_format($item->jumlah_rencana, 0, ',', '.') }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-slate-700">Rp{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right font-bold {{ $item->selisih >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp{{ number_format(abs($item->selisih), 0, ',', '.') }}</td>
                                                <td class="px-3 py-2.5">
                                                    @php
                                                        $statusLabel = ['belum' => 'Belum', 'proses' => 'Proses', 'selesai' => 'Selesai'][$item->status] ?? $item->status;
                                                        $statusClass = ['belum' => 'bg-slate-100 text-slate-600', 'proses' => 'bg-amber-50 text-amber-700', 'selesai' => 'bg-emerald-50 text-emerald-700'][$item->status] ?? 'bg-slate-100 text-slate-600';
                                                    @endphp
                                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <button wire:click="edit('{{ $item->id }}')" class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Ubah</button>
                                                        @if (auth()->user()?->hasRole('admin'))
                                                            <button wire:click="confirmDelete('{{ $item->id }}', 'item RAB ini')" class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                                        @endif
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

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
