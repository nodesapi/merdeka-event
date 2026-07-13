<x-layouts.public title="Laporan" :eventName="$event?->name">
    <div>
        <span class="merdeka-badge">Laporan</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Laporan Pemasukan &amp; Pengeluaran</h1>
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

    <section class="mt-6">
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500">Rencana vs Realisasi Anggaran (RAB)</h2>
        <p class="mt-1 text-xs text-stone-400">Rincian rencana anggaran biaya per kategori dan realisasi penggunaannya.</p>

        @if ($event && $event->recommended_contribution_amount && $event->contribution_target_households)
            @php
                $targetDana = (float) $event->contribution_target_amount;
                $selisihTarget = $targetDana - $totalRabRencana;
            @endphp
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div class="merdeka-card p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-600">Iuran per Rumah</p>
                    <p class="mt-2 text-3xl font-extrabold text-stone-900">Rp{{ number_format($event->recommended_contribution_amount, 0, ',', '.') }}</p>
                </div>
                <div class="merdeka-card p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-600">Target Rumah</p>
                    <p class="mt-2 text-3xl font-extrabold text-stone-900">{{ number_format($event->contribution_target_households, 0, ',', '.') }} <span class="text-lg font-semibold text-stone-400">rumah</span></p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-700">Target Dana Iuran</p>
                    <p class="mt-2 text-3xl font-extrabold text-red-700">Rp{{ number_format($targetDana, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-4 rounded-xl border p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between {{ $selisihTarget >= 0 ? 'border-emerald-200 bg-emerald-700' : 'border-red-200 bg-red-700' }}">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-white/80">{{ $selisihTarget >= 0 ? 'Surplus — Target Dana Cukup' : 'Defisit — Target Dana Kurang' }}</p>
                    <p class="mt-1 text-sm text-white/90">Total kebutuhan anggaran (RAB) Rp{{ number_format($totalRabRencana, 0, ',', '.') }} dibanding target dana iuran Rp{{ number_format($targetDana, 0, ',', '.') }}</p>
                </div>
                <p class="text-3xl font-extrabold sm:text-4xl">Rp{{ number_format(abs($selisihTarget), 0, ',', '.') }}</p>
            </div>
        @endif

        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div class="merdeka-card p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-stone-600">Total Rencana</p>
                <p class="mt-2 text-2xl font-extrabold text-stone-900">Rp{{ number_format($totalRabRencana, 0, ',', '.') }}</p>
            </div>
            <div class="merdeka-card p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Total Realisasi</p>
                <p class="mt-2 text-2xl font-extrabold text-stone-900">Rp{{ number_format($totalRabRealisasi, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border p-5 text-white shadow-sm {{ $totalRabSelisih >= 0 ? 'border-emerald-200 bg-emerald-700' : 'border-red-200 bg-red-700' }}">
                <p class="text-xs font-bold uppercase tracking-wide text-white/80">Selisih</p>
                <p class="mt-2 text-2xl font-extrabold">Rp{{ number_format(abs($totalRabSelisih), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="merdeka-card mt-4 overflow-hidden">
            <div class="overflow-x-auto px-5 py-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        <tr><th class="py-2 pr-4">Kategori</th><th class="py-2 pr-4 text-right">Rencana</th><th class="py-2 pr-4 text-right">Realisasi</th><th class="py-2 text-right">Selisih</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-600">
                        @forelse ($rabByCategory as $kategori => $group)
                            <tr>
                                <td class="py-2.5 pr-4 font-semibold text-stone-900">{{ $kategori }}</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($group['rencana'], 0, ',', '.') }}</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($group['realisasi'], 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right font-bold {{ $group['selisih'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Rp{{ number_format(abs($group['selisih']), 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-stone-400">Belum ada data RAB.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($rabByCategory->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-stone-200 font-bold text-stone-900">
                                <td class="py-2.5 pr-4">Total</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($totalRabRencana, 0, ',', '.') }}</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($totalRabRealisasi, 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right">Rp{{ number_format(abs($totalRabSelisih), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </section>
</x-layouts.public>
