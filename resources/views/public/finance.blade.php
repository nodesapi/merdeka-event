<x-layouts.public title="Transparansi Dana" :eventName="$event?->name">
    <div>
        <span class="merdeka-badge">Laporan Dana</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Transparansi Pemasukan &amp; Pengeluaran</h1>
        <p class="mt-1.5 max-w-2xl text-sm leading-6 text-stone-500">Warga dapat memantau dana masuk dan dana keluar secara terbuka.</p>
        <a href="{{ route('public.family-form') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-red-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-red-800">
            <x-icon name="users" class="h-4 w-4" /> Isi Form Warga
        </a>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="merdeka-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Dana Terkumpul</p>
            <p class="mt-2 text-2xl font-extrabold text-stone-900">Rp{{ number_format($totalIncome, 0, ',', '.') }}</p>
        </div>
        <div class="merdeka-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-red-700">Dana Dikeluarkan</p>
            <p class="mt-2 text-2xl font-extrabold text-stone-900">Rp{{ number_format($totalExpense, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-700 p-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-100">Saldo Tersedia</p>
            <p class="mt-2 text-2xl font-extrabold">Rp{{ number_format($balance, 0, ',', '.') }}</p>
        </div>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="merdeka-card overflow-hidden">
            <div class="border-b border-stone-100 bg-emerald-50/60 px-5 py-3.5">
                <p class="text-sm font-bold text-emerald-800">Dana Masuk</p>
                <p class="text-xs text-emerald-700">{{ $incomeTransactions->count() }} transaksi tercatat</p>
            </div>
            <div class="overflow-x-auto px-5 py-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        <tr><th class="py-2 pr-4">Pengirim</th><th class="py-2 pr-4">Catatan</th><th class="py-2 text-right">Nominal</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-600">
                        @forelse ($incomeTransactions as $transaction)
                            <tr>
                                <td class="py-2.5 pr-4 align-top">
                                    <p class="font-semibold text-stone-900">{{ $transaction->user?->name ?? 'Warga' }}</p>
                                    <p class="text-xs text-stone-400">{{ $transaction->resident_block }}</p>
                                </td>
                                <td class="py-2.5 pr-4 align-top">{{ $transaction->description }}</td>
                                <td class="py-2.5 text-right align-top font-bold text-emerald-700">Rp{{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-stone-400">Belum ada pemasukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="merdeka-card overflow-hidden">
            <div class="border-b border-stone-100 bg-red-50/60 px-5 py-3.5">
                <p class="text-sm font-bold text-red-800">Dana Keluar</p>
                <p class="text-xs text-red-700">{{ $expenseTransactions->count() }} transaksi tercatat</p>
            </div>
            <div class="overflow-x-auto px-5 py-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        <tr><th class="py-2 pr-4">PJ</th><th class="py-2 pr-4">Catatan</th><th class="py-2 text-right">Nominal</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-600">
                        @forelse ($expenseTransactions as $transaction)
                            <tr>
                                <td class="py-2.5 pr-4 align-top">
                                    <p class="font-semibold text-stone-900">{{ $transaction->user?->name ?? 'Panitia' }}</p>
                                    <p class="text-xs text-stone-400">{{ $transaction->resident_block }}</p>
                                </td>
                                <td class="py-2.5 pr-4 align-top">{{ $transaction->description }}</td>
                                <td class="py-2.5 text-right align-top font-bold text-red-700">Rp{{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-stone-400">Belum ada pengeluaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.public>
