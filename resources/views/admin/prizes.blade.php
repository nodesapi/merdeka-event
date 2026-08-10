<x-layouts.app title="Rekap Hadiah" header="Rekap Kebutuhan Hadiah">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.competitions') }}" class="text-xs font-semibold text-red-600 hover:underline">&larr; Kembali ke daftar lomba</a>
            <p class="mt-1 text-sm text-slate-500">
                Tiap kategori umur (lomba individu) atau tiap lomba grup butuh 1 set Juara 1/2/3 sendiri.
                Total di bawah asumsi 3 hadiah per kategori/lomba, apa pun jumlah pesertanya.
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('admin.prizes', ['format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                <x-icon name="wallet" class="h-4 w-4" /> Excel
            </a>
            <a href="{{ route('admin.prizes', ['format' => 'pdf']) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                <x-icon name="calendar" class="h-4 w-4" /> PDF
            </a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Lomba</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $rows->count() }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><x-icon name="flag" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Total Kategori / Set Juara</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalCategories }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><x-icon name="trophy" class="h-6 w-6" /></span>
        </div>
        <div class="flex items-center justify-between rounded-lg border border-red-600 bg-red-600 p-5 text-white shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-red-100">Perkiraan Total Hadiah</p>
                <p class="mt-2 text-2xl font-bold">{{ $totalCategories * 3 }}</p>
                <p class="text-[11px] text-red-100">{{ $totalCategories }} kategori &times; 3 (Juara 1/2/3)</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white/15 text-white"><x-icon name="gift" class="h-6 w-6" /></span>
        </div>
    </div>

    <div class="space-y-6">
        @forelse ($rows as $row)
            @php $competition = $row['competition']; @endphp
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-slate-100 bg-slate-50">
                    <span class="w-2 h-4 bg-red-600 rounded"></span>
                    <h4 class="font-semibold text-slate-900">{{ $competition->name }}</h4>
                    @if ($competition->isGroup())
                        <span class="text-xs px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">Grup</span>
                    @endif
                    <span class="text-xs px-2 py-0.5 rounded {{ $competition->status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-500' }}">{{ $competition->status }}</span>
                </div>

                @if ($row['categories']->isEmpty())
                    <p class="px-6 py-4 text-sm text-slate-400">Belum ada peserta{{ $competition->isGroup() ? '/tim' : '' }} terdaftar.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] table-fixed text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/60 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="w-1/2 px-6 py-2.5">Kategori</th>
                                    <th class="w-1/4 px-6 py-2.5">{{ $competition->isGroup() ? 'Jumlah Tim' : 'Jumlah Peserta' }}</th>
                                    <th class="w-1/4 px-6 py-2.5">Estimasi Hadiah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($row['categories'] as $category)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-3 font-medium text-slate-900">{{ $category['label'] }}</td>
                                        <td class="px-6 py-3 text-slate-600">{{ $category['count'] }}</td>
                                        <td class="px-6 py-3 text-slate-600">3 (Juara 1/2/3)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">
                Belum ada lomba. Tambahkan lewat menu <a href="{{ route('admin.competitions') }}" class="font-semibold text-red-600 hover:underline">Lomba</a> terlebih dahulu.
            </div>
        @endforelse
    </div>
</x-layouts.app>
