<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Support\Str;

new class extends Component
{
    public ?string $editingId = null;

    public $name = '';
    public $target_participants = '';
    public $total_rounds = 1;
    public $status = 'published';
    public $description = '';

    public $success_message = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'target_participants' => 'nullable|string|max:255',
            'total_rounds' => 'required|integer|min:1|max:20',
            'status' => 'required|in:draft,published,closed',
            'description' => 'nullable|string',
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
            $this->addError('name', 'Belum ada event aktif.');
            return;
        }

        if ($this->editingId) {
            $competition = Competition::findOrFail($this->editingId);
            $competition->update($data);
            $this->success_message = 'Lomba "' . $this->name . '" berhasil diperbarui!';
        } else {
            $data['event_id'] = $event->id;
            $data['slug'] = $this->uniqueSlug($this->name);
            Competition::create($data);
            $this->success_message = 'Lomba "' . $this->name . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (Competition::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function edit(string $id)
    {
        $competition = Competition::findOrFail($id);
        $this->editingId = $competition->id;
        $this->name = $competition->name;
        $this->target_participants = $competition->target_participants;
        $this->total_rounds = $competition->total_rounds;
        $this->status = $competition->status;
        $this->description = $competition->description;
    }

    public function delete(string $id)
    {
        Competition::where('id', $id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Lomba berhasil dihapus.';
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'name', 'target_participants', 'description']);
        $this->total_rounds = 1;
        $this->status = 'published';
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        return [
            'competitions' => $event
                ? $event->competitions()->withCount('participants')->orderBy('name')->get()
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
                {{ $editingId ? 'Ubah Lomba' : 'Tambah Lomba' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lomba</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Lomba Makan Kerupuk">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Target Peserta</label>
                    <input type="text" wire:model="target_participants" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Anak-anak dan Remaja">
                    @error('target_participants') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah Babak</label>
                        <input type="number" wire:model="total_rounds" min="1" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500">
                        @error('total_rounds') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                        <select wire:model="status" data-custom-select class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="draft">Draft</option>
                            <option value="published">Publikasi</option>
                            <option value="closed">Ditutup</option>
                        </select>
                        @error('status') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Deskripsi</label>
                    <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Keterangan singkat lomba"></textarea>
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-md text-sm font-medium hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Lomba' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-4 flex items-center justify-between">
                <span>Daftar Lomba</span>
                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $competitions->count() }} lomba</span>
            </h3>
            <div class="divide-y divide-slate-100">
                @forelse ($competitions as $competition)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-slate-900 truncate">{{ $competition->name }}</p>
                                <span class="text-xs px-2 py-0.5 rounded {{ $competition->status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-500' }}">{{ $competition->status }}</span>
                            </div>
                            <p class="text-xs text-slate-500">{{ $competition->target_participants }} · {{ $competition->total_rounds }} babak · {{ $competition->participants_count }} peserta</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <a href="{{ route('admin.participants', $competition->slug) }}" class="text-xs px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium">Peserta &amp; Juara</a>
                            <button wire:click="edit('{{ $competition->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                            <button wire:click="delete('{{ $competition->id }}')" wire:confirm="Hapus lomba ini beserta pesertanya?" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-400 text-sm">Belum ada lomba. Tambahkan lewat form di samping.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
