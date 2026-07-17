<?php

use Livewire\Component;
use App\Models\BazaarSubmission;
use App\Models\Event;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Str;

new class extends Component
{
    use ConfirmsDeletion;

    public ?string $editingId = null;

    public $name = '';
    public $resident_block = '';
    public $phone_number = '';
    public $jenis_jualan = '';

    public $success_message = '';

    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'resident_block' => 'required|string|max:100',
            'phone_number' => 'required|string|max:50',
            'jenis_jualan' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $event = $this->activeEvent();
        if (! $event) {
            $this->addError('name', 'Belum ada event aktif. Buat event terlebih dahulu.');
            return;
        }

        $family = BazaarSubmission::resolveEligibleFamily($event, $data['phone_number']);
        if (! $family) {
            $this->addError('phone_number', 'Nomor HP ini belum terdaftar di Data Warga.');
            return;
        }

        $existing = BazaarSubmission::where('family_submission_id', $family->id)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->first();
        if ($existing) {
            $this->addError('phone_number', 'Keluarga ini sudah terdaftar bazaar dengan jenis jualan "' . $existing->jenis_jualan . '". Satu keluarga hanya boleh 1 lapak.');
            return;
        }

        $jenisJualan = trim(preg_replace('/\s+/', ' ', $data['jenis_jualan']));
        if (BazaarSubmission::jenisJualanTaken($event, $jenisJualan, $this->editingId)) {
            $this->addError('jenis_jualan', 'Jenis jualan "' . $jenisJualan . '" sudah didaftarkan warga lain.');
            return;
        }

        $payload = [
            'name' => $data['name'],
            'resident_block' => $data['resident_block'],
            'phone_number' => $data['phone_number'],
            'jenis_jualan' => $jenisJualan,
            'family_submission_id' => $family->id,
        ];

        if ($this->editingId) {
            BazaarSubmission::where('id', $this->editingId)->update($payload);
            $this->success_message = 'Data lapak "' . $data['name'] . '" berhasil diperbarui!';
        } else {
            $payload['event_id'] = $event->id;
            $payload['reference_code'] = 'BZR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
            BazaarSubmission::create($payload);
            $this->success_message = 'Lapak "' . $data['name'] . '" berhasil ditambahkan!';
        }

        $this->resetForm();
    }

    public function edit(string $id)
    {
        $item = BazaarSubmission::findOrFail($id);
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->resident_block = $item->resident_block;
        $this->phone_number = $item->phone_number;
        $this->jenis_jualan = $item->jenis_jualan;
    }

    public function delete(string $id)
    {
        BazaarSubmission::where('id', $id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->success_message = 'Data lapak berhasil dihapus.';
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'name', 'resident_block', 'phone_number', 'jenis_jualan']);
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        return [
            'submissions' => $event
                ? $event->bazaarSubmissions()->with('familySubmission')->latest()->get()
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
                {{ $editingId ? 'Ubah Lapak' : 'Tambah Lapak' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Pendaftar</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Budi Santoso">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Blok / Rumah</label>
                    <input type="text" wire:model="resident_block" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: A/01">
                    @error('resident_block') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">No HP</label>
                    <input type="text" wire:model="phone_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0812xxxxxxx">
                    <p class="mt-1 text-xs text-slate-400">Harus sudah terdaftar di Data Warga.</p>
                    @error('phone_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Jualan</label>
                    <input type="text" wire:model="jenis_jualan" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Es Jeruk">
                    @error('jenis_jualan') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-md text-sm font-medium hover:bg-slate-50">Batal</button>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Lapak' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-semibold text-base text-slate-900 flex items-center gap-2">
                    <span>Form Bazaar</span>
                    <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $submissions->count() }} lapak</span>
                </h3>
                <div class="flex gap-2">
                    <a href="{{ route('admin.bazaar-submissions.export', ['format' => 'csv']) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <x-icon name="wallet" class="h-4 w-4" /> Excel
                    </a>
                    <a href="{{ route('admin.bazaar-submissions.export', ['format' => 'pdf']) }}" target="_blank" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        <x-icon name="calendar" class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($submissions as $item)
                    <div wire:key="bazaar-{{ $item->id }}" class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="shrink-0 rounded-md bg-red-50 border border-red-100 px-2.5 py-1 text-xs font-bold text-red-700">{{ $item->jenis_jualan }}</span>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $item->name }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $item->resident_block }} &middot; {{ $item->phone_number }}
                                    @if ($item->familySubmission)
                                        &middot; KK: {{ $item->familySubmission->head_of_family_name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="edit('{{ $item->id }}')" class="text-xs px-3 py-1.5 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Ubah</button>
                            <button wire:click="confirmDelete('{{ $item->id }}', 'lapak ini')" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-400 text-sm">Belum ada warga yang daftar bazaar.</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
