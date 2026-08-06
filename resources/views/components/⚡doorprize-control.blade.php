<?php

use Livewire\Component;
use App\Models\DoorprizeWinner;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\SiteSetting;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Cache;

new class extends Component
{
    use ConfirmsDeletion;

    public ?string $eventId = null;
    public ?string $eventName = null;
    public string $prizeNameInput = '';
    public string $status = 'idle';
    public ?string $currentPrizeName = null;
    public ?array $lastWinner = null;
    public int $poolCount = 0;
    public array $winners = [];
    public string $errorMessage = '';

    public function mount()
    {
        $event = Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
        $site = SiteSetting::current();

        $this->eventId = $event?->id;
        $this->eventName = $event?->name ?: ($site->site_name ?: config('app.name'));

        $this->refreshState();
    }

    protected function cacheKey(): string
    {
        return "doorprize:state:{$this->eventId}";
    }

    /**
     * Kepala keluarga = anggota keluarga dengan No Daftar terkecil per pendaftaran —
     * kepala keluarga selalu dimasukkan pertama saat isi Form Warga
     * (lihat PublicController::storeFamilyForm), jadi otomatis dapat No Daftar terkecil.
     */
    protected function eligibleHeads()
    {
        if (! $this->eventId) {
            return collect();
        }

        return FamilyMember::where('event_id', $this->eventId)
            ->whereNotNull('registration_number')
            ->whereHas('familySubmission', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->with('familySubmission:id,resident_block')
            ->get()
            ->groupBy('family_submission_id')
            ->map(fn ($members) => $members->sortBy(fn ($m) => (int) $m->registration_number)->first())
            ->values();
    }

    protected function pool(): array
    {
        $wonIds = DoorprizeWinner::where('event_id', $this->eventId)->pluck('family_member_id')->all();

        return $this->eligibleHeads()
            ->reject(fn ($m) => in_array($m->id, $wonIds, true))
            ->map(fn ($m) => [
                'id' => $m->id,
                'registration_number' => $m->registration_number,
                'name' => $m->name,
                'resident_block' => $m->familySubmission?->resident_block,
            ])
            ->values()
            ->all();
    }

    public function refreshState(): void
    {
        if (! $this->eventId) {
            $this->status = 'idle';
            $this->currentPrizeName = null;
            $this->poolCount = 0;
            $this->winners = [];
            return;
        }

        $state = Cache::get($this->cacheKey(), ['status' => 'idle', 'prize_name' => null]);
        $this->status = $state['status'] ?? 'idle';
        $this->currentPrizeName = $state['prize_name'] ?? null;
        $this->poolCount = count($this->pool());

        $this->winners = DoorprizeWinner::where('event_id', $this->eventId)
            ->with('familyMember.familySubmission:id,resident_block')
            ->orderByDesc('drawn_at')
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'registration_number' => $w->familyMember?->registration_number,
                'name' => $w->familyMember?->name,
                'resident_block' => $w->familyMember?->familySubmission?->resident_block,
                'prize_name' => $w->prize_name,
                'drawn_at' => $w->drawn_at?->format('H:i:s'),
            ])
            ->all();
    }

    /**
     * Mulai putaran: BELUM ada pemenang yang dipilih di sini, cuma menyalakan status
     * "spinning" yang dibaca halaman layar publik. Pemenang baru diacak saat stop().
     */
    public function start()
    {
        $this->errorMessage = '';
        $this->refreshState();

        if (! $this->eventId) {
            $this->errorMessage = 'Belum ada acara aktif.';
            return;
        }

        if ($this->status === 'spinning') {
            $this->errorMessage = 'Undian sedang berjalan.';
            return;
        }

        if ($this->poolCount === 0) {
            $this->errorMessage = 'Semua kepala keluarga sudah kebagian menang, atau belum ada yang terdaftar.';
            return;
        }

        Cache::put($this->cacheKey(), [
            'status' => 'spinning',
            'prize_name' => $this->prizeNameInput ?: null,
            'started_at' => now()->toIso8601String(),
            'winner' => null,
        ], now()->addHours(6));

        $this->lastWinner = null;
        $this->refreshState();
    }

    /**
     * Titik acak sesungguhnya: pemenang baru dipilih random di sini, tepat saat
     * panitia klik stop — bukan sebelumnya. Jadi tidak ada hasil yang "sudah ada"
     * sebelum diputar.
     */
    public function stop()
    {
        $this->errorMessage = '';
        $state = Cache::get($this->cacheKey(), ['status' => 'idle']);

        if (($state['status'] ?? 'idle') !== 'spinning') {
            $this->errorMessage = 'Tidak ada undian yang sedang berjalan.';
            $this->refreshState();
            return;
        }

        $pool = $this->pool();

        if ($pool === []) {
            Cache::forget($this->cacheKey());
            $this->errorMessage = 'Kolam undian kosong, undian dibatalkan.';
            $this->refreshState();
            return;
        }

        $winner = collect($pool)->random();
        $prizeName = $state['prize_name'] ?? null;

        try {
            DoorprizeWinner::create([
                'event_id' => $this->eventId,
                'family_member_id' => $winner['id'],
                'prize_name' => $prizeName,
                'drawn_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException) {
            $this->errorMessage = 'Terjadi bentrok data (kemungkinan diklik dobel), silakan undi ulang.';
            Cache::forget($this->cacheKey());
            $this->refreshState();
            return;
        }

        Cache::put($this->cacheKey(), [
            'status' => 'revealed',
            'prize_name' => $prizeName,
            'winner' => $winner,
            'revealed_at' => now()->toIso8601String(),
        ], now()->addMinutes(15));

        $this->lastWinner = $winner;
        $this->prizeNameInput = '';
        $this->refreshState();
    }

    /**
     * Batalkan putaran yang sedang berjalan TANPA memilih pemenang (belum ada
     * pemenang yang diacak sama sekali di titik ini, jadi tidak ada data yang dihapus).
     * $id diabaikan — cuma supaya bisa dipanggil generik lewat ConfirmsDeletion.
     */
    public function cancelSpin(?string $id = null)
    {
        Cache::forget($this->cacheKey());
        $this->refreshState();
    }

    public function undoLast(?string $id = null)
    {
        if (! $this->eventId) {
            return;
        }

        $last = DoorprizeWinner::where('event_id', $this->eventId)->orderByDesc('drawn_at')->first();

        if ($last) {
            $last->delete();
        }

        Cache::forget($this->cacheKey());
        $this->lastWinner = null;
        $this->refreshState();
    }

    /**
     * Hapus SEMUA riwayat pemenang untuk acara ini — dipakai buat uji coba
     * berulang sebelum hari-H, supaya kolam kepala keluarga penuh lagi.
     * Sengaja dipisah dari undoLast() dan butuh konfirmasi tegas di UI.
     */
    public function resetAll(?string $id = null)
    {
        if (! $this->eventId) {
            return;
        }

        DoorprizeWinner::where('event_id', $this->eventId)->delete();
        Cache::forget($this->cacheKey());

        $this->lastWinner = null;
        $this->prizeNameInput = '';
        $this->errorMessage = '';
        $this->refreshState();
    }
};
?>

<div class="w-full">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Kontrol Undian Doorprize</h3>
            <p class="text-sm text-slate-500">{{ $eventName ?: 'Belum ada acara aktif' }}</p>
        </div>
        <a href="{{ route('public.doorprize') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
            Buka Layar Tampilan &nearr;
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($status === 'spinning')
                <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    <x-icon name="sparkles" class="h-4 w-4 shrink-0 translate-y-0.5" />
                    <span>Undian sedang berputar di layar tampilan{{ $currentPrizeName ? ' — hadiah: ' . $currentPrizeName : '' }}. Klik "Berhentikan & Tampilkan" saat sudah siap.</span>
                </div>
            @elseif ($lastWinner)
                <div class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    <x-icon name="trophy" class="h-4 w-4 shrink-0" />
                    <span>Pemenang terakhir: <span class="font-mono font-bold">{{ $lastWinner['registration_number'] }}</span> {{ $lastWinner['name'] }}</span>
                </div>
            @endif

            @if ($errorMessage)
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $errorMessage }}</div>
            @endif

            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nama Hadiah (opsional)</label>
            <input type="text" wire:model="prizeNameInput" @disabled($status === 'spinning') placeholder="Contoh: Kipas Angin" class="w-full max-w-md rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 disabled:bg-slate-100">

            <div class="mt-5 flex flex-wrap items-center gap-3">
                @if ($status === 'spinning')
                    <button wire:click="stop" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-red-700"><x-icon name="stop" class="h-4 w-4" /> Berhentikan &amp; Tampilkan</button>
                    <button wire:click="confirmDelete('spin', 'putaran yang sedang berjalan', 'cancelSpin')" class="text-xs font-medium text-slate-500 underline hover:text-slate-800">Batalkan putaran</button>
                @else
                    <button wire:click="start" @disabled($poolCount === 0) class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"><x-icon name="sparkles" class="h-4 w-4" /> Undi Sekarang</button>
                @endif
            </div>

            <p class="mt-4 text-xs text-slate-500">Sisa {{ $poolCount }} kepala keluarga belum menang · {{ count($winners) }} sudah diundi</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">
                <h4 class="text-sm font-semibold text-slate-900">Riwayat Pemenang</h4>
                @if ($winners !== [])
                    <button wire:click="confirmDelete('last-winner', 'pemenang terakhir dari riwayat', 'undoLast')" class="text-xs font-medium text-red-600 hover:underline">Batalkan terakhir</button>
                @endif
            </div>
            @if ($winners !== [])
                <div class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($winners as $w)
                        <div class="flex items-center justify-between px-5 py-2.5 text-sm">
                            <span><span class="font-mono font-bold text-red-600">{{ $w['registration_number'] }}</span> {{ $w['name'] }}</span>
                            <span class="text-xs text-slate-400">{{ $w['prize_name'] ?: '-' }} · {{ $w['drawn_at'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="px-5 py-6 text-center text-sm text-slate-400">Belum ada pemenang.</p>
            @endif
        </div>
    </div>

    @if ($winners !== [])
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50/60 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Zona Berbahaya — khusus uji coba</p>
            <p class="mt-1 text-xs text-red-600/80">Hapus SEMUA riwayat pemenang di acara ini supaya kolam kepala keluarga penuh lagi buat latihan. Jangan diklik saat acara sungguhan sedang berjalan.</p>
            <button
                wire:click="confirmDelete('all-winners', 'SEMUA riwayat pemenang doorprize untuk acara ini', 'resetAll')"
                class="mt-3 inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-100"
            >
                <x-icon name="trash" class="h-4 w-4" /> Reset Semua Pemenang
            </button>
        </div>
    @endif

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
