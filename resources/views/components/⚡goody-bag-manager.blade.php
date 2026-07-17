<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\GoodyBagItem;
use App\Models\Event;
use App\Support\ImageConverter;
use App\Traits\ConfirmsDeletion;

new class extends Component
{
    use WithFileUploads, ConfirmsDeletion;

    public ?string $editingId = null;

    public $name = '';
    public $description = '';
    public $sort_order = 0;

    public $photo;
    public ?string $photoPath = null;

    public $success_message = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'photo' => 'nullable|image|max:4096',
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
        unset($data['photo']);

        $event = $this->activeEvent();
        if (! $event) {
            $this->addError('name', 'Belum ada event aktif. Buat event terlebih dahulu.');
            return;
        }

        $data['sort_order'] = $this->sort_order ?: 0;

        if ($this->photo) {
            ImageConverter::delete($this->photoPath);
            $data['photo'] = ImageConverter::storeAsWebp($this->photo, 'goody-bag', 512);
        }

        if ($this->editingId) {
            GoodyBagItem::where('id', $this->editingId)->update($data);
            $this->success_message = 'Item "' . $this->name . '" berhasil diperbarui!';
        } else {
            GoodyBagItem::create(array_merge($data, [
                'event_id' => $event->id,
            ]));
            $this->success_message = 'Item "' . $this->name . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id)
    {
        $item = GoodyBagItem::findOrFail($id);
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->sort_order = $item->sort_order;
        $this->photoPath = $item->photo;
        $this->reset('photo');
    }

    public function delete(string $id)
    {
        $item = GoodyBagItem::find($id);
        if ($item) {
            ImageConverter::delete($item->photo);
            $item->delete();
        }
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Item berhasil dihapus.';
    }

    public function removePhoto()
    {
        ImageConverter::delete($this->photoPath);
        if ($this->editingId) {
            GoodyBagItem::where('id', $this->editingId)->update(['photo' => null]);
        }
        $this->photoPath = null;
        $this->reset('photo');
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'name', 'description', 'sort_order', 'photo', 'photoPath']);
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
            'items' => $event
                ? $event->goodyBagItems()->orderBy('sort_order')->orderBy('name')->get()
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
                {{ $editingId ? 'Ubah Item Goody Bag' : 'Tambah Item Goody Bag' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Barang</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Kaos HUT RI">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Foto <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <div class="flex items-center gap-3">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover">
                            @elseif ($photoPath)
                                <img src="{{ '/storage/' . ltrim($photoPath, '/') }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-red-600 to-red-800 text-white">
                                    <x-icon name="gift" class="h-6 w-6" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" wire:model="photo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                            <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                            @error('photo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if ($photoPath)
                                <button type="button" wire:click="removePhoto" class="mt-1 text-xs text-red-500 hover:underline">Hapus foto</button>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Ukuran M/L/XL, warna merah"></textarea>
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
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
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Item' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-4 flex items-center justify-between">
                <span>Isi Goody Bag</span>
                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $items->count() }} item</span>
            </h3>
            <div class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if ($item->photo_url)
                                    <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-red-600 to-red-800 text-white">
                                        <x-icon name="gift" class="h-4 w-4" />
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $item->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $item->description ?: '-' }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="edit('{{ $item->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                            <button wire:click="confirmDelete('{{ $item->id }}', 'item ini')" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-400 text-sm">Belum ada item goody bag. Tambahkan lewat form di samping.</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
