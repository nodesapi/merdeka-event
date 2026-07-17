<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Sponsor;
use App\Models\Event;
use App\Support\ImageConverter;
use App\Traits\ConfirmsDeletion;

new class extends Component
{
    use WithFileUploads, ConfirmsDeletion;

    public ?string $editingId = null;

    public $name = '';
    public $sort_order = 0;

    public $logo;
    public ?string $logoPath = null;

    public $success_message = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'logo' => 'nullable|image|max:2048',
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
        unset($data['logo']);

        $event = $this->activeEvent();
        if (! $event) {
            $this->addError('name', 'Belum ada event aktif. Buat event terlebih dahulu.');
            return;
        }

        $data['sort_order'] = $this->sort_order ?: 0;

        if ($this->logo) {
            ImageConverter::delete($this->logoPath);
            $data['logo'] = ImageConverter::storeAsWebp($this->logo, 'sponsors', 512);
        }

        if ($this->editingId) {
            Sponsor::where('id', $this->editingId)->update($data);
            $this->success_message = 'Sponsor "' . $this->name . '" berhasil diperbarui!';
        } else {
            Sponsor::create(array_merge($data, [
                'event_id' => $event->id,
            ]));
            $this->success_message = 'Sponsor "' . $this->name . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->sort_order = $sponsor->sort_order;
        $this->logoPath = $sponsor->logo;
        $this->reset('logo');
    }

    public function delete(string $id)
    {
        $sponsor = Sponsor::find($id);
        if ($sponsor) {
            ImageConverter::delete($sponsor->logo);
            $sponsor->delete();
        }
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Sponsor berhasil dihapus.';
    }

    public function removeLogo()
    {
        ImageConverter::delete($this->logoPath);
        if ($this->editingId) {
            Sponsor::where('id', $this->editingId)->update(['logo' => null]);
        }
        $this->logoPath = null;
        $this->reset('logo');
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'name', 'sort_order', 'logo', 'logoPath']);
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
            'sponsors' => $event
                ? $event->sponsors()->orderBy('sort_order')->orderBy('name')->get()
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

    <div class="grid grid-cols-1 lg:grid-cols-[0.9fr_1.1fr] gap-8">
        <!-- Form -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-fit">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                {{ $editingId ? 'Ubah Sponsor' : 'Tambah Sponsor' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Sponsor</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Toko Bangunan Jaya">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Logo <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <div class="flex items-center gap-3">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" class="h-full w-full object-contain">
                            @elseif ($logoPath)
                                <img src="{{ '/storage/' . ltrim($logoPath, '/') }}" class="h-full w-full object-contain">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-red-600 to-red-800 text-white">
                                    <x-icon name="sparkles" class="h-6 w-6" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" wire:model="logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                            <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                            @error('logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if ($logoPath)
                                <button type="button" wire:click="removeLogo" class="mt-1 text-xs text-red-500 hover:underline">Hapus logo</button>
                            @endif
                        </div>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">Kosongkan bila hanya ingin menampilkan nama sponsor tanpa logo.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Urutan Tampil</label>
                    <input type="number" wire:model="sort_order" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0">
                    @error('sort_order') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-md text-sm font-medium hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Sponsor' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-4 flex items-center justify-between">
                <span>Daftar Sponsor</span>
                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $sponsors->count() }} sponsor</span>
            </h3>
            <div class="divide-y divide-slate-100">
                @forelse ($sponsors as $sponsor)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if ($sponsor->logo_url)
                                    <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="h-full w-full object-contain">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-red-600 to-red-800 text-white">
                                        <x-icon name="sparkles" class="h-4 w-4" />
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $sponsor->name }}</p>
                                <p class="text-xs text-slate-400">Urutan {{ $sponsor->sort_order }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="edit('{{ $sponsor->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                            <button wire:click="confirmDelete('{{ $sponsor->id }}', 'sponsor ini')" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-400 text-sm">Belum ada sponsor. Tambahkan lewat form di samping.</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
