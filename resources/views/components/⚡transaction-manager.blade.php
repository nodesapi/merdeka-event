<?php

use App\Models\Transaction;
use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public string $type = 'income';
    public $amount = '';
    public string $description = '';
    public string $resident_block = '';
    public string $bank_name = '';
    public string $account_number = '';

    public string $filter = 'all';
    public string $search = '';

    public string $success_message = '';

    public function save(): void
    {
        $data = $this->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'resident_block' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
        ]);

        $user = $this->resident_block
            ? User::where('resident_block', 'like', '%' . $this->resident_block . '%')->first()
            : null;

        Transaction::create([
            'user_id' => $user?->id,
            'amount' => $data['amount'],
            'type' => $data['type'],
            'bank_name' => $data['bank_name'] ?: null,
            'account_number' => $data['account_number'] ?: null,
            'resident_block' => $data['resident_block'] ?: null,
            'description' => $data['description'],
            'status' => 'approved',
        ]);

        $label = $this->type === 'expense' ? 'Pengeluaran' : 'Pemasukan';
        $this->success_message = $label . ' sebesar Rp' . number_format((float) $this->amount, 0, ',', '.') . ' berhasil dicatat.';

        $this->reset(['amount', 'description', 'resident_block', 'bank_name', 'account_number']);
        $this->type = 'income';
    }

    public function delete(string $id): void
    {
        Transaction::whereKey($id)->delete();
        $this->success_message = 'Transaksi berhasil dihapus.';
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->sum('amount');

        $query = Transaction::with('user')->latest();

        if (in_array($this->filter, ['income', 'expense'], true)) {
            $query->where('type', $this->filter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                    ->orWhere('resident_block', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhere('account_number', 'like', $term);
            });
        }

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'transactions' => $query->take(100)->get(),
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

    {{-- Ringkasan --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Dana Masuk</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format($totalIncome, 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><x-icon name="arrow-down-tray" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-red-700">Dana Keluar</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format($totalExpense, 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600"><x-icon name="arrow-up-tray" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-emerald-600 bg-emerald-600 p-5 text-white shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100">Saldo</p>
                <p class="mt-2 text-2xl font-bold">Rp{{ number_format($balance, 0, ',', '.') }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white/20 text-white"><x-icon name="wallet" class="h-6 w-6" /></span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)]">
        {{-- Form --}}
        <div class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Catat Transaksi
            </h3>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Jenis Transaksi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-3 py-2.5 text-sm font-semibold transition {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-500' }}">
                            <input type="radio" wire:model.live="type" value="income" class="sr-only"> Masuk
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-3 py-2.5 text-sm font-semibold transition {{ $type === 'expense' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-300 text-slate-500' }}">
                            <input type="radio" wire:model.live="type" value="expense" class="sr-only"> Keluar
                        </label>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Nominal (Rp)</label>
                    <div class="relative" data-rupiah-input>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                        <input type="text" value="{{ $amount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-md border border-slate-300 py-2.5 pl-12 pr-4 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="100.000">
                        <input type="hidden" wire:model="amount" value="{{ $amount }}" data-rupiah-hidden>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Ketik 100000, otomatis jadi 100.000.</p>
                    @error('amount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Keterangan</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: Iuran warga / Beli hadiah lomba"></textarea>
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Blok / Penanggung Jawab <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input type="text" wire:model="resident_block" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="A/12">
                    @error('resident_block') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Bank / Metode <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="bank_name" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="BCA / Tunai">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">No. Rek / Ref <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="account_number" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="8800012345">
                    </div>
                </div>
                <button type="submit" class="w-full rounded-md bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Simpan Transaksi</button>
            </form>
        </div>

        {{-- Datatable --}}
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between sm:p-5">
                <div class="inline-flex w-fit rounded-md border border-slate-200 bg-slate-50 p-1 text-sm font-semibold">
                    @foreach (['all' => 'Semua', 'income' => 'Masuk', 'expense' => 'Keluar'] as $key => $label)
                        <button wire:click="$set('filter', '{{ $key }}')" class="rounded px-3 py-1.5 transition {{ $filter === $key ? 'bg-white text-red-700 shadow-sm' : 'text-slate-500' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:w-52" placeholder="Cari keterangan / blok...">
                    <div class="flex gap-2">
                        <a href="{{ route('admin.transactions.export', ['format' => 'csv', 'filter' => $filter]) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            <x-icon name="wallet" class="h-4 w-4" /> Excel
                        </a>
                        <a href="{{ route('admin.transactions.export', ['format' => 'pdf', 'filter' => $filter]) }}" target="_blank" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                            <x-icon name="calendar" class="h-4 w-4" /> PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Jenis</th>
                            <th class="px-4 py-3 font-semibold">Keterangan</th>
                            <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $trx)
                            <tr class="border-b border-slate-100 odd:bg-white even:bg-slate-50/60 hover:bg-red-50/30">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $trx->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if ($trx->type === 'expense')
                                        <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">Keluar</span>
                                    @else
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">Masuk</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $trx->description }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $trx->resident_block ?: '-' }}
                                        @if ($trx->bank_name) · {{ $trx->bank_name }} @endif
                                        @if ($trx->account_number) · {{ $trx->account_number }} @endif
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold {{ $trx->type === 'expense' ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $trx->type === 'expense' ? '-' : '+' }}Rp{{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button wire:click="delete('{{ $trx->id }}')" wire:confirm="Hapus transaksi ini?" class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($transactions->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-slate-200 bg-slate-50 text-sm font-bold text-slate-700">
                                <td class="px-4 py-3" colspan="3">{{ $transactions->count() }} transaksi ditampilkan</td>
                                <td class="px-4 py-3 text-right">Saldo: Rp{{ number_format($balance, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
