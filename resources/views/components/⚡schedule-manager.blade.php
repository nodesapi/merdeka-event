<?php

use Livewire\Component;
use App\Models\EventSchedule;
use App\Models\Event;

new class extends Component
{
    public ?string $editingId = null;

    public $time_label = '';
    public $activity = '';
    public $sort_order = 0;

    public $success_message = '';

    protected function rules(): array
    {
        return [
            'time_label' => 'required|string|max:100',
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

    public function resetForm()
    {
        $this->reset(['editingId', 'time_label', 'activity', 'sort_order']);
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
                ? $event->eventSchedules()->orderBy('sort_order')->orderBy('time_label')->get()
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
            <h3 class="font-semibold text-base text-slate-900 mb-4 flex items-center justify-between">
                <span>Susunan Acara</span>
                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $schedules->count() }} kegiatan</span>
            </h3>
            <div class="divide-y divide-slate-100">
                @forelse ($schedules as $item)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="shrink-0 rounded-md bg-red-50 border border-red-100 px-2.5 py-1 text-xs font-bold text-red-700">{{ $item->time_label }}</span>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $item->activity }}</p>
                                <p class="text-xs text-slate-400">#{{ $item->sort_order }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="edit('{{ $item->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                            <button wire:click="delete('{{ $item->id }}')" wire:confirm="Hapus jadwal ini?" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-400 text-sm">Belum ada susunan acara. Tambahkan lewat form di samping.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
