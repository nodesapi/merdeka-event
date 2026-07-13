<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\EventSchedule;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithFileUploads;

    public ?string $editingId = null;

    public $time_label = '';
    public $scheduled_at = '';
    public $activity = '';
    public $sort_order = 0;

    public $success_message = '';

    public $scheduleImportFile = null;
    public array $importErrors = [];

    protected function rules(): array
    {
        return [
            'time_label' => 'required|string|max:100',
            'scheduled_at' => 'nullable|date',
            'activity' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    public function save()
    {
        $data = $this->validate();

        $event = $this->activeEvent();
        if (! $event) {
            $this->addError('activity', 'Belum ada event aktif. Buat event terlebih dahulu.');
            return;
        }

        $data['sort_order'] = $this->sort_order ?: 0;
        $data['scheduled_at'] = $this->scheduled_at ?: null;

        if ($this->editingId) {
            EventSchedule::where('id', $this->editingId)->update($data);
            $this->success_message = 'Jadwal "' . $this->activity . '" berhasil diperbarui!';
        } else {
            EventSchedule::create(array_merge($data, [
                'event_id' => $event->id,
            ]));
            $this->success_message = 'Jadwal "' . $this->activity . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id)
    {
        $item = EventSchedule::findOrFail($id);
        $this->editingId = $item->id;
        $this->time_label = $item->time_label;
        $this->scheduled_at = $item->scheduled_at?->format('Y-m-d\TH:i');
        $this->activity = $item->activity;
        $this->sort_order = $item->sort_order;
    }

    public function delete(string $id)
    {
        EventSchedule::where('id', $id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Jadwal berhasil dihapus.';
    }

    /**
     * Upload CSV hasil export (kolom: ID, Tanggal, Jam, Waktu, Nama Kegiatan, Urutan Tampil).
     * Baris dengan ID yang cocok akan diperbarui, baris tanpa ID/tidak cocok akan ditambahkan baru.
     * Bersifat upsert-only — tidak pernah menghapus baris yang hilang dari file.
     */
    public function importSchedule()
    {
        $this->importErrors = [];

        $this->validate([
            'scheduleImportFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $event = $this->activeEvent();
        if (! $event) {
            $this->addError('scheduleImportFile', 'Belum ada event aktif. Buat event terlebih dahulu.');
            return;
        }

        $handle = fopen($this->scheduleImportFile->getRealPath(), 'r');
        if (! $handle) {
            $this->addError('scheduleImportFile', 'File tidak bisa dibaca.');
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

        DB::transaction(function () use ($handle, $event, &$created, &$updated, &$errors, &$rowNumber) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                [$id, $tanggal, $jam, $timeLabel, $activity, $sortOrder] = array_pad($row, 6, null);

                $timeLabel = trim((string) $timeLabel);
                $activity = trim((string) $activity);

                if ($timeLabel === '' || $activity === '') {
                    $errors[] = "Baris {$rowNumber}: Waktu/Nama Kegiatan kosong, dilewati.";
                    continue;
                }

                $scheduledAt = null;
                $tanggal = trim((string) $tanggal);
                $jam = trim((string) $jam);

                if ($tanggal !== '') {
                    try {
                        $scheduledAt = \Illuminate\Support\Carbon::parse($tanggal . ' ' . ($jam !== '' ? $jam : '00:00'));
                    } catch (\Throwable $e) {
                        $errors[] = "Baris {$rowNumber}: format tanggal/jam tidak valid, tanggal dikosongkan.";
                    }
                }

                $data = [
                    'time_label' => $timeLabel,
                    'scheduled_at' => $scheduledAt,
                    'activity' => $activity,
                    'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 0,
                ];

                $id = trim((string) $id);
                $existing = $id !== '' ? EventSchedule::where('id', $id)->where('event_id', $event->id)->first() : null;

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    EventSchedule::create(array_merge($data, ['event_id' => $event->id]));
                    $created++;
                }
            }
        });

        fclose($handle);

        $this->importErrors = $errors;
        $this->success_message = "Import selesai: {$created} kegiatan ditambahkan, {$updated} diperbarui."
            . (count($errors) ? ' ' . count($errors) . ' baris dilewati, lihat detail di bawah.' : '');

        $this->reset(['scheduleImportFile']);
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'time_label', 'scheduled_at', 'activity', 'sort_order']);
        $this->sort_order = 0;
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        return [
            'schedules' => $event
                ? $event->eventSchedules()->orderBy('scheduled_at')->orderBy('sort_order')->orderBy('time_label')->get()
                : collect(),
        ];
    }
};
?>

<div>
    @if ($success_message)
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center justify-between shadow-sm">
            <span class="font-medium text-sm">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="text-emerald-500 hover:text-emerald-800 font-bold">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[0.8fr_1.2fr] gap-8">
        <!-- Form -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-fit">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                {{ $editingId ? 'Ubah Jadwal' : 'Tambah Jadwal' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Waktu</label>
                    <input type="text" wire:model="time_label" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: 07.00 - 08.00">
                    @error('time_label') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal &amp; Jam</label>
                    <div wire:ignore>
                        <input type="hidden" wire:model.live="scheduled_at" value="{{ $scheduled_at }}" data-custom-datetime data-custom-datetime-placeholder="Pilih tanggal dan jam">
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Dipakai untuk urutan kronologis, terutama kalau acara berlangsung lebih dari satu hari.</p>
                    @error('scheduled_at') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Kegiatan</label>
                    <input type="text" wire:model="activity" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Senam Pagi Bersama">
                    @error('activity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Urutan Tampil</label>
                    <input type="number" wire:model="sort_order" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0">
                    <p class="mt-1 text-xs text-slate-400">Angka kecil tampil lebih dulu.</p>
                    @error('sort_order') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-md text-sm font-medium hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Jadwal' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-semibold text-base text-slate-900 flex items-center gap-2">
                    <span>Susunan Acara</span>
                    <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $schedules->count() }} kegiatan</span>
                </h3>
                <div class="flex gap-2">
                    <a href="{{ route('admin.schedule.export', ['format' => 'csv']) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <x-icon name="wallet" class="h-4 w-4" /> Excel
                    </a>
                    <a href="{{ route('admin.schedule.export', ['format' => 'pdf']) }}" target="_blank" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        <x-icon name="calendar" class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>

            <div class="mb-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-xs font-semibold text-slate-600">Import dari Excel</p>
                <p class="mt-1 text-xs text-slate-400">Upload file CSV hasil edit (kolom ID jangan diubah/dihapus). Baris yang diedit/kolom ID cocok akan diperbarui, baris baru tanpa ID akan ditambahkan. Baris yang dihapus di Excel <span class="font-semibold">tidak ikut terhapus</span> di sistem — hapus manual lewat tombol Hapus.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="file" wire:model="scheduleImportFile" accept=".csv,text/csv" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300">
                    <button type="button" wire:click="importSchedule" wire:loading.attr="disabled" wire:target="scheduleImportFile,importSchedule" class="shrink-0 rounded-md bg-slate-700 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        <span wire:loading.remove wire:target="importSchedule">Import</span>
                        <span wire:loading wire:target="scheduleImportFile,importSchedule">Memproses...</span>
                    </button>
                </div>
                @error('scheduleImportFile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                @if (!empty($importErrors))
                    <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800">
                        <p class="font-semibold">Baris dilewati:</p>
                        <ul class="mt-1 list-disc pl-4">
                            @foreach ($importErrors as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if ($schedules->isEmpty())
                <p class="py-4 text-center text-slate-400 text-sm">Belum ada susunan acara. Tambahkan lewat form di samping.</p>
            @else
                @php
                    $groupedSchedules = $schedules->groupBy(fn ($item) => optional($item->scheduled_at)->format('Y-m-d') ?? 'tbd');
                @endphp
                @foreach ($groupedSchedules as $dateKey => $daySchedules)
                    <div wire:key="schedule-group-{{ $dateKey }}" class="mb-5 last:mb-0">
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                            @if ($dateKey === 'tbd')
                                Waktu Belum Ditentukan
                            @else
                                {{ \Illuminate\Support\Carbon::parse($dateKey)->locale('id')->translatedFormat('l, d F Y') }}
                            @endif
                        </h4>
                        <div class="divide-y divide-slate-100">
                            @foreach ($daySchedules as $item)
                                <div wire:key="schedule-{{ $item->id }}" class="flex items-center justify-between gap-3 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="shrink-0 rounded-md bg-red-50 border border-red-100 px-2.5 py-1 text-xs font-bold text-red-700">{{ $item->time_label }}</span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900 truncate">{{ $item->activity }}</p>
                                            <p class="text-xs text-slate-400">
                                                @if ($item->scheduled_at)
                                                    {{ $item->scheduled_at->format('H:i') }} &middot;
                                                @endif
                                                #{{ $item->sort_order }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <button wire:click="edit('{{ $item->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                                        <button wire:click="delete('{{ $item->id }}')" wire:confirm="Hapus jadwal ini?" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
