<?php

use App\Models\ResidentChild;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public $resident_name = '';
    public $resident_email = '';
    public $resident_block = '';
    public $phone_number = '';
    public $children = [];

    public string $search = '';
    public string $success_message = '';

    public function mount(): void
    {
        $this->children = $this->blankChildren();
    }

    protected function blankChildren(int $count = 3): array
    {
        return array_map(fn () => ['name' => '', 'age' => '', 'gender' => 'L'], range(1, $count));
    }

    public function addChild(): void
    {
        $this->children[] = ['name' => '', 'age' => '', 'gender' => 'L'];
    }

    public function removeChild($index): void
    {
        unset($this->children[$index]);
        $this->children = array_values($this->children);
    }

    public function saveResident(): void
    {
        $this->validate([
            'resident_name' => 'required|string|max:255',
            'resident_email' => 'required|email|unique:users,email',
            'resident_block' => 'required|string|max:50',
            'phone_number' => 'nullable|string|max:50',
            'children.*.name' => 'nullable|string|max:255',
            'children.*.age' => 'nullable|integer|min:0|max:100',
        ]);

        $user = User::create([
            'name' => $this->resident_name,
            'email' => $this->resident_email,
            'password' => bcrypt(Str::random(16)),
            'resident_block' => $this->resident_block,
            'phone_number' => $this->phone_number,
        ]);

        foreach ($this->children as $child) {
            if (! empty($child['name'])) {
                ResidentChild::create([
                    'user_id' => $user->id,
                    'name' => $child['name'],
                    'age' => $child['age'] !== '' ? $child['age'] : null,
                    'gender' => $child['gender'] ?? 'L',
                ]);
            }
        }

        $this->success_message = 'Data warga "' . $user->name . '" berhasil disimpan.';
        $this->reset(['resident_name', 'resident_email', 'resident_block', 'phone_number']);
        $this->children = $this->blankChildren();
    }

    public function delete(string $id): void
    {
        User::whereKey($id)->delete();
        $this->success_message = 'Data warga dihapus.';
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $query = User::with('children')->latest();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('resident_block', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return [
            'residents' => $query->take(100)->get(),
            'totalResidents' => User::count(),
            'totalChildren' => ResidentChild::count(),
        ];
    }
};
?>

<div>
    @if ($success_message)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
            <span class="text-sm font-medium">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="text-lg font-bold leading-none text-emerald-500 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
        {{-- Form --}}
        <div class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Registrasi Data Warga &amp; Anak
            </h3>
            <form wire:submit.prevent="saveResident" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Lengkap</label>
                        <input type="text" wire:model="resident_name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Nama Warga">
                        @error('resident_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                        <input type="email" wire:model="resident_email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="warga@email.com">
                        @error('resident_email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Blok Rumah</label>
                        <input type="text" wire:model="resident_block" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: A/12">
                        @error('resident_block') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">No. HP / WhatsApp</label>
                        <input type="text" wire:model="phone_number" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="0812345678">
                        @error('phone_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="space-y-3 rounded-xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-700">Data Anak (untuk kategori lomba)</label>
                        <button type="button" wire:click="addChild" class="text-xs font-semibold text-red-600 hover:text-red-700">+ Tambah Anak</button>
                    </div>
                    @foreach ($children as $index => $child)
                        <div class="grid grid-cols-12 items-center gap-2">
                            <input type="text" wire:model="children.{{ $index }}.name" class="col-span-6 rounded-lg border border-slate-300 px-3 py-2 text-xs outline-none focus:border-red-500" placeholder="Nama Anak">
                            <input type="number" wire:model="children.{{ $index }}.age" class="col-span-3 rounded-lg border border-slate-300 px-3 py-2 text-xs outline-none focus:border-red-500" placeholder="Usia">
                            <select wire:model="children.{{ $index }}.gender" class="col-span-2 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs outline-none focus:border-red-500">
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select>
                            <div class="col-span-1 text-center">
                                @if (count($children) > 1)
                                    <button type="button" wire:click="removeChild({{ $index }})" class="font-bold text-slate-400 hover:text-red-600">&times;</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Simpan Data Warga</button>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar Warga</h3>
                    <p class="text-xs text-slate-500">{{ $totalResidents }} warga · {{ $totalChildren }} anak terdaftar</p>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:w-56" placeholder="Cari nama / blok...">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Blok</th>
                            <th class="px-4 py-3">Anak</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($residents as $resident)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $resident->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $resident->phone_number ?: $resident->email }}</p>
                                </td>
                                <td class="px-4 py-3"><span class="rounded border border-red-100 bg-red-50 px-2 py-0.5 text-xs text-red-700">{{ $resident->resident_block ?: '-' }}</span></td>
                                <td class="px-4 py-3">
                                    @if ($resident->children->count())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($resident->children as $child)
                                                <span class="rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700">{{ $child->name }}{{ $child->age ? ' (' . $child->age . ')' : '' }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button wire:click="delete('{{ $resident->id }}')" wire:confirm="Hapus data warga ini?" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada data warga.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
