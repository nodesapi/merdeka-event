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
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500">Sumber Dana</h2>
        <p class="mt-1 text-xs text-stone-400">Perkiraan dana yang akan terkumpul, dari iuran, sponsor, donasi, dan sumber lainnya.</p>

        <div class="merdeka-card mt-3 overflow-hidden">
            <div class="overflow-x-auto px-5 py-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        <tr>
                            <th class="py-2 pr-4">Kategori / Sumber</th>
                            <th class="py-2 pr-4">Keterangan</th>
                            <th class="py-2 pr-4 text-right">Target</th>
                            <th class="py-2 text-right">Realisasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-600">
                        @if ($event && $event->recommended_contribution_amount && $event->contribution_target_households)
                            <tr class="bg-stone-50">
                                <td colspan="4" class="py-2 pr-4 font-bold text-stone-900">Iuran</td>
                            </tr>
                            <tr>
                                <td class="py-2.5 pr-4 pl-6 align-top">Total Iuran Warga</td>
                                <td class="py-2.5 pr-4 align-top text-stone-500">Rp{{ number_format($event->recommended_contribution_amount, 0, ',', '.') }}/rumah &times; {{ number_format($event->contribution_target_households, 0, ',', '.') }} rumah</td>
                                <td class="py-2.5 pr-4 text-right align-top">Rp{{ number_format($iuranTarget, 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right align-top font-semibold text-emerald-700">Rp{{ number_format($iuranRealisasi, 0, ',', '.') }}</td>
                            </tr>
                            @if ($sisaSetelahIuran > 0)
                                <tr>
                                    <td colspan="4" class="py-1.5 pr-4 pl-6 text-xs italic text-amber-700">Sisa kebutuhan setelah Iuran: Rp{{ number_format($sisaSetelahIuran, 0, ',', '.') }} — perlu dicari dari Sponsor/Donasi/sumber lain di bawah.</td>
                                </tr>
                            @endif
                        @endif
                        @forelse ($fundingByCategory as $kategori => $group)
                            <tr class="bg-stone-50">
                                <td colspan="4" class="py-2 pr-4 font-bold text-stone-900">{{ $kategori }}</td>
                            </tr>
                            @foreach ($group['items'] as $item)
                                <tr>
                                    <td class="py-2.5 pr-4 pl-6 align-top">{{ $item->sumber }}</td>
                                    <td class="py-2.5 pr-4 align-top text-stone-500">{{ $item->catatan ?: '-' }}</td>
                                    <td class="py-2.5 pr-4 text-right align-top">Rp{{ number_format($item->target, 0, ',', '.') }}</td>
                                    <td class="py-2.5 text-right align-top font-semibold text-emerald-700">Rp{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @empty
                            @if (! ($event && $event->recommended_contribution_amount && $event->contribution_target_households))
                                <tr><td colspan="4" class="py-4 text-center text-stone-400">Belum ada data sumber dana.</td></tr>
                            @endif
                        @endforelse
                    </tbody>
                    @if ($fundingByCategory->isNotEmpty() || ($event && $event->recommended_contribution_amount && $event->contribution_target_households))
                        <tfoot>
                            <tr class="border-t-2 border-stone-200 font-bold text-stone-900">
                                <td class="py-2.5 pr-4" colspan="2">Total</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($iuranTarget + $fundingByCategory->sum('target'), 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right">Rp{{ number_format($totalRealisasiDana, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @php
            $selisihEstimasi = $totalRealisasiDana - $totalRabRencana;
        @endphp
        <div class="mt-4 flex flex-col gap-4 rounded-xl border p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between {{ $selisihEstimasi >= 0 ? 'border-emerald-200 bg-emerald-700' : 'border-red-200 bg-red-700' }}">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-white/80">{{ $selisihEstimasi >= 0 ? 'Sudah Tercukupi' : 'Masih Kurang' }}</p>
                <p class="mt-1 text-sm text-white/90">Realisasi Dana Terkumpul Rp{{ number_format($totalRealisasiDana, 0, ',', '.') }} dibanding Total Kebutuhan Anggaran / Target Dana (RAB) Rp{{ number_format($totalRabRencana, 0, ',', '.') }}</p>
            </div>
            <p class="text-3xl font-extrabold sm:text-4xl">Rp{{ number_format(abs($selisihEstimasi), 0, ',', '.') }}</p>
        </div>

        <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-stone-500">Rincian Kebutuhan RAB</h2>
        <p class="mt-1 text-xs text-stone-400">Rincian kategori dan sub-kategori kebutuhan anggaran, rencana vs realisasi penggunaannya.</p>

        <div class="merdeka-card mt-3 overflow-hidden">
            <div class="overflow-x-auto px-5 py-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        <tr>
                            <th class="py-2 pr-4">Kategori / Rincian</th>
                            <th class="py-2 pr-4">Qty</th>
                            <th class="py-2 pr-4 text-right">Nominal</th>
                            <th class="py-2 pr-4 text-right">Rencana</th>
                            <th class="py-2 pr-4 text-right">Realisasi</th>
                            <th class="py-2 pr-4 text-right">Selisih</th>
                            <th class="py-2">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-600">
                        @forelse ($rabByCategory as $kategori => $group)
                            <tr class="bg-stone-50">
                                <td colspan="7" class="py-2 pr-4 font-bold text-stone-900">{{ $kategori }}</td>
                            </tr>
                            @foreach ($group['items'] as $item)
                                <tr>
                                    <td class="py-2.5 pr-4 pl-6 align-top">{{ $item->nama_item }}</td>
                                    <td class="py-2.5 pr-4 align-top">{{ (float) $item->volume }}{{ $item->satuan ? ' ' . $item->satuan : '' }}</td>
                                    <td class="py-2.5 pr-4 text-right align-top">Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="py-2.5 pr-4 text-right align-top">Rp{{ number_format($item->jumlah_rencana, 0, ',', '.') }}</td>
                                    <td class="py-2.5 pr-4 text-right align-top">Rp{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                                    <td class="py-2.5 pr-4 text-right align-top font-semibold {{ $item->selisih >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Rp{{ number_format(abs($item->selisih), 0, ',', '.') }}</td>
                                    <td class="py-2.5 align-top text-stone-500">{{ $item->catatan ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="py-4 text-center text-stone-400">Belum ada data RAB.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($rabByCategory->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-stone-200 font-bold text-stone-900">
                                <td class="py-2.5 pr-4" colspan="3">Total Estimasi Anggaran</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($totalRabRencana, 0, ',', '.') }}</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format($totalRabRealisasi, 0, ',', '.') }}</td>
                                <td class="py-2.5 pr-4 text-right">Rp{{ number_format(abs($totalRabSelisih), 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </section>
</x-layouts.public>
