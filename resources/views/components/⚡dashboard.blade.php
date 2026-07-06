<?php

use App\Models\CommitteeMember;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\ContributionItem;
use App\Models\Event;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use Livewire\Component;

new class extends Component
{
    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->sum('amount');

        $submissionsQuery = $event ? $event->familySubmissions() : FamilySubmission::query()->whereRaw('1=0');

        $contribByType = ContributionItem::selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'event' => $event,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'submissionsTotal' => (clone $submissionsQuery)->count(),
            'submissionsVerified' => (clone $submissionsQuery)->where('status', 'verified')->count(),
            'submissionsPending' => (clone $submissionsQuery)->where('status', 'pending')->count(),
            'participantsCount' => CompetitionParticipant::count(),
            'competitionsCount' => Competition::count(),
            'transactionsCount' => Transaction::count(),
            'committeeCount' => CommitteeMember::where('is_active', true)->count(),
            'contribByType' => $contribByType,
            'recentSubmissions' => (clone $submissionsQuery)->latest()->take(6)->get(),
            'recentTransactions' => Transaction::with('user')->latest()->take(7)->get(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Ringkasan dana --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-red-600 to-red-700 p-5 text-white shadow-sm">
            <div class="flex items-center gap-2 text-red-100"><x-icon name="wallet" class="h-5 w-5" /><span class="text-xs font-semibold uppercase tracking-[0.14em]">Saldo Kas</span></div>
            <p class="mt-3 text-2xl font-black">Rp{{ number_format($balance, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 text-emerald-600"><x-icon name="arrow-right" class="h-5 w-5 -rotate-45" /><span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Dana Masuk</span></div>
            <p class="mt-3 text-2xl font-black text-slate-900">Rp{{ number_format($totalIncome, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 text-red-600"><x-icon name="arrow-right" class="h-5 w-5 rotate-45" /><span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Dana Keluar</span></div>
            <p class="mt-3 text-2xl font-black text-slate-900">Rp{{ number_format($totalExpense, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('admin.family-submissions') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-red-300">
            <div class="flex items-center gap-2 text-slate-500"><x-icon name="users" class="h-5 w-5" /><span class="text-xs font-semibold uppercase tracking-[0.14em]">Pendaftaran Warga</span></div>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $submissionsTotal }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $submissionsVerified }} terverifikasi · {{ $submissionsPending }} menunggu</p>
        </a>
    </div>

    {{-- Mini stats --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Lomba', 'value' => $competitionsCount, 'icon' => 'trophy', 'route' => 'admin.competitions'],
            ['label' => 'Peserta Lomba', 'value' => $participantsCount, 'icon' => 'medal', 'route' => 'admin.competitions'],
            ['label' => 'Panitia', 'value' => $committeeCount, 'icon' => 'crown', 'route' => 'admin.committee'],
            ['label' => 'Transaksi', 'value' => $transactionsCount, 'icon' => 'wallet', 'route' => 'admin.transactions'],
        ] as $stat)
            <a href="{{ route($stat['route']) }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-red-300">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600"><x-icon name="{{ $stat['icon'] }}" class="h-5 w-5" /></span>
                <div>
                    <p class="text-xl font-black leading-none text-slate-900">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['label'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Arus dana --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-slate-900">Arus Dana</h3>
            <p class="text-xs text-slate-500">Perbandingan pemasukan dan pengeluaran</p>
            @php $flowMax = max($totalIncome, $totalExpense, 1); @endphp
            <div class="mt-5 space-y-4">
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm"><span class="font-medium text-slate-600">Masuk</span><span class="font-bold text-emerald-600">Rp{{ number_format($totalIncome, 0, ',', '.') }}</span></div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ round($totalIncome / $flowMax * 100, 1) }}%"></div></div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm"><span class="font-medium text-slate-600">Keluar</span><span class="font-bold text-red-600">Rp{{ number_format($totalExpense, 0, ',', '.') }}</span></div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-red-500" style="width: {{ round($totalExpense / $flowMax * 100, 1) }}%"></div></div>
                </div>
            </div>
            <div class="mt-5 rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo Tersedia</p>
                <p class="mt-1 text-xl font-black text-slate-900">Rp{{ number_format($balance, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Komposisi kontribusi --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-slate-900">Komposisi Kontribusi Warga</h3>
            <p class="text-xs text-slate-500">Total per jenis kontribusi dari form warga</p>
            @php
                $palette = ['iuran' => 'bg-red-500', 'donasi' => 'bg-amber-500', 'sponsor' => 'bg-emerald-500'];
                $contribMax = max($contribByType->max() ?: 0, 1);
            @endphp
            <div class="mt-5 space-y-4">
                @forelse ($contribByType as $type => $total)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm"><span class="font-medium capitalize text-slate-600">{{ $type }}</span><span class="font-bold text-slate-900">Rp{{ number_format($total, 0, ',', '.') }}</span></div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $palette[$type] ?? 'bg-slate-500' }}" style="width: {{ round($total / $contribMax * 100, 1) }}%"></div></div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Belum ada data kontribusi.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent lists --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-slate-900">Pendaftaran Warga Terbaru</h3>
                <a href="{{ route('admin.family-submissions') }}" class="text-xs font-semibold text-red-600 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentSubmissions as $s)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $s->head_of_family_name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $s->reference_code }} · {{ $s->resident_block }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-slate-900">Rp{{ number_format($s->submitted_total, 0, ',', '.') }}</p>
                            <p class="text-[11px] font-semibold uppercase {{ $s->status === 'verified' ? 'text-emerald-600' : ($s->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ $s->status }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada pendaftaran warga.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-slate-900">Transaksi Terbaru</h3>
                <a href="{{ route('admin.transactions') }}" class="text-xs font-semibold text-red-600 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentTransactions as $trx)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $trx->description }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $trx->created_at?->format('d/m/Y H:i') }} · {{ $trx->resident_block ?: '-' }}</p>
                        </div>
                        <p class="whitespace-nowrap text-sm font-bold {{ $trx->type === 'expense' ? 'text-red-600' : 'text-emerald-600' }}">{{ $trx->type === 'expense' ? '-' : '+' }}Rp{{ number_format($trx->amount, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada transaksi.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
