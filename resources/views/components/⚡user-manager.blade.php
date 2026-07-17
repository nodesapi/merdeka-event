<?php

use App\Models\User;
use App\Traits\ConfirmsDeletion;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    use ConfirmsDeletion;

    public ?string $editingId = null;

    public string $name = '';
    public string $username = '';
    public ?string $email = '';
    public string $role = 'panitia';
    public string $resident_block = '';
    public string $phone_number = '';
    public string $password = '';

    public string $search = '';
    public string $success_message = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($this->editingId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(['admin', 'panitia'])],
            'resident_block' => 'nullable|string|max:50',
            'phone_number' => 'nullable|string|max:50',
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
        ];
    }

    public function saveUser(): void
    {
        if ($this->email === '') {
            $this->email = null;
        }

        $data = $this->validate();

        // Jangan biarkan admin terakhir menurunkan dirinya sendiri jadi non-admin.
        if ($this->editingId === auth()->id() && $data['role'] !== 'admin'
            && User::role('admin')->count() <= 1) {
            $this->addError('role', 'Tidak bisa menurunkan role admin terakhir.');
            return;
        }

        $attributes = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'resident_block' => $data['resident_block'] ?: null,
            'phone_number' => $data['phone_number'] ?: null,
        ];

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->fill($attributes);
            if (filled($this->password)) {
                $user->password = $this->password;
            }
            $user->save();
            $user->syncRoles([$data['role']]);
            $this->success_message = 'User "' . $user->name . '" berhasil diperbarui.';
        } else {
            $user = User::create($attributes + ['password' => $this->password]);
            $user->syncRoles([$data['role']]);
            $this->success_message = 'User "' . $user->name . '" berhasil ditambahkan.';
        }

        $this->resetForm();
    }

    public function editUser(string $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email ?? '';
        $this->role = $user->getRoleNames()->first() ?? 'panitia';
        $this->resident_block = $user->resident_block ?? '';
        $this->phone_number = $user->phone_number ?? '';
        $this->password = '';
        $this->resetErrorBag();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'username', 'email', 'resident_block', 'phone_number', 'password']);
        $this->role = 'panitia';
        $this->resetErrorBag();
    }

    public function deleteUser(string $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang boleh menghapus user.');

        if ($id === auth()->id()) {
            $this->success_message = 'Tidak bisa menghapus akun yang sedang login.';
            return;
        }

        $user = User::find($id);
        if (! $user) {
            return;
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            $this->success_message = 'Tidak bisa menghapus admin terakhir.';
            return;
        }

        $user->delete();
        $this->success_message = 'User dihapus.';

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $query = User::with('roles')->latest();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return [
            'users' => $query->take(100)->get(),
            'totalUsers' => User::count(),
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

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        {{-- Form --}}
        <div class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span>
                {{ $editingId ? 'Edit User Panitia' : 'Tambah User Panitia' }}
            </h3>
            <form wire:submit.prevent="saveUser" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Lengkap</label>
                    <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Nama panitia">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Username</label>
                        <input type="text" wire:model="username" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="mis. sekretaris">
                        @error('username') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Role / Akses</label>
                        <select wire:model="role" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
                            <option value="panitia">Panitia</option>
                            <option value="admin">Admin (akses penuh)</option>
                        </select>
                        @error('role') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Email <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input type="email" wire:model="email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="panitia@email.com">
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Blok / Rumah <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="resident_block" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="A/12">
                        @error('resident_block') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">No. HP <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="phone_number" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="0812345678">
                        @error('phone_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Password @if ($editingId)<span class="font-normal text-slate-400">(kosongkan jika tidak diubah)</span>@endif</label>
                    <input type="text" wire:model="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Minimal 6 karakter">
                    @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">{{ $editingId ? 'Simpan Perubahan' : 'Tambah User' }}</button>
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Batal</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar User Panitia</h3>
                    <p class="text-xs text-slate-500">{{ $totalUsers }} akun</p>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:w-56" placeholder="Cari nama / username...">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nama / Username</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Kontak</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $u)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $u->name }}
                                        @if ($u->id === auth()->id())<span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500">Anda</span>@endif
                                    </p>
                                    <p class="font-mono text-xs text-slate-400">{{ '@' . ($u->username ?: '-') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @php $r = $u->getRoleNames()->first(); @endphp
                                    @if ($r === 'admin')
                                        <span class="rounded-full border border-red-100 bg-red-50 px-2.5 py-0.5 text-[11px] font-semibold text-red-700">Admin</span>
                                    @elseif ($r === 'panitia')
                                        <span class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700">Panitia</span>
                                    @else
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500">{{ ucfirst($r ?: '-') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    <p>{{ $u->email ?: '-' }}</p>
                                    <p class="text-slate-400">{{ $u->phone_number ?: '' }}{{ $u->resident_block ? ' · ' . $u->resident_block : '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click="editUser('{{ $u->id }}')" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Edit</button>
                                        @if ($u->id !== auth()->id() && auth()->user()?->hasRole('admin'))
                                            <button wire:click="confirmDelete('{{ $u->id }}', @js('user ' . $u->name), 'deleteUser')" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
