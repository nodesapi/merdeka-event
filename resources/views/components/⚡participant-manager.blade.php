<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\CompetitionParticipant;

new class extends Component
{
    public string $competitionId;
    public string $competitionName = '';
    public string $competitionSlug = '';
    public int $totalRounds = 1;

    // New participant form
    public $name = '';
    public $resident_block = '';
    public $phone_number = '';

    public $success_message = '';

    public function mount(Competition $competition)
    {
        $this->competitionId = $competition->id;
        $this->competitionName = $competition->name;
        $this->competitionSlug = $competition->slug;
        $this->totalRounds = $competition->total_rounds;
    }

    protected function participant(string $id): CompetitionParticipant
    {
        return CompetitionParticipant::where('competition_id', $this->competitionId)->findOrFail($id);
    }

    public function addParticipant()
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'resident_block' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:50',
        ]);

        CompetitionParticipant::create(array_merge($data, [
            'competition_id' => $this->competitionId,
            'round' => 1,
            'status' => 'active',
        ]));

        $this->success_message = 'Peserta "' . $this->name . '" berhasil ditambahkan.';
        $this->reset(['name', 'resident_block', 'phone_number']);
    }

    public function promote(string $id)
    {
        $participant = $this->participant($id);

        if ($participant->status === 'eliminated') {
            $this->success_message = 'Peserta sudah gugur. Aktifkan kembali sebelum menaikkan babak.';
            return;
        }

        if ($participant->round >= $this->totalRounds) {
            $this->success_message = $participant->name . ' sudah berada di babak final (babak ' . $this->totalRounds . ').';
            return;
        }

        $participant->increment('round');
        $this->success_message = $participant->name . ' naik ke babak ' . $participant->round . '.';
    }

    public function demote(string $id)
    {
        $participant = $this->participant($id);
        if ($participant->round > 1) {
            $participant->decrement('round');
        }
    }

    public function eliminate(string $id)
    {
        $participant = $this->participant($id);
        $participant->update(['status' => 'eliminated', 'rank' => null]);
        $this->success_message = $participant->name . ' ditandai gugur.';
    }

    public function reinstate(string $id)
    {
        $this->participant($id)->update(['status' => 'active']);
    }

    public function setRank(string $id, $rank)
    {
        $rank = (int) $rank;
        $participant = $this->participant($id);

        // Only one participant per rank in a competition — clear any existing holder.
        CompetitionParticipant::where('competition_id', $this->competitionId)
            ->where('rank', $rank)
            ->where('id', '!=', $participant->id)
            ->update(['rank' => null]);

        $participant->update(['rank' => $rank, 'status' => 'active']);
        $this->success_message = $participant->name . ' ditetapkan sebagai Juara ' . $rank . '.';
    }

    public function clearRank(string $id)
    {
        $this->participant($id)->update(['rank' => null]);
    }

    public function delete(string $id)
    {
        $this->participant($id)->delete();
        $this->success_message = 'Peserta dihapus.';
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $participants = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->orderByDesc('round')
            ->orderBy('rank')
            ->orderBy('name')
            ->get();

        return [
            'participantsByRound' => $participants->groupBy('round')->sortKeysDesc(),
            'totalParticipants' => $participants->count(),
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

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.competitions') }}" class="text-xs font-semibold text-red-600 hover:underline">&larr; Kembali ke daftar lomba</a>
            <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $competitionName }}</h3>
            <p class="text-sm text-slate-500">{{ $totalParticipants }} peserta · {{ $totalRounds }} babak</p>
        </div>
        <a href="{{ route('public.competition.show', $competitionSlug) }}" target="_blank" class="text-xs px-3 py-2 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Lihat halaman publik &rarr;</a>
    </div>

    <!-- Add participant -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
        <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
            <span class="w-2 h-4 bg-red-600 rounded"></span>
            Tambah Peserta
        </h3>
        <form wire:submit.prevent="addParticipant" class="grid grid-cols-1 md:grid-cols-[1.5fr_1fr_1fr_auto] gap-4 md:items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Peserta / Regu</label>
                <input type="text" wire:model="name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Nama peserta">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Blok / Asal</label>
                <input type="text" wire:model="resident_block" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="A/12">
                @error('resident_block') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">No. HP (opsional)</label>
                <input type="text" wire:model="phone_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0812xxxx">
                @error('phone_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Tambah</button>
        </form>
    </div>

    <!-- Participants grouped by round -->
    @forelse ($participantsByRound as $round => $participants)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-3 border-b border-slate-100 bg-slate-50">
                <h4 class="font-semibold text-slate-900">Babak {{ $round }}{{ $round == $totalRounds ? ' (Final)' : '' }}</h4>
                <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $participants->count() }} peserta</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($participants as $p)
                    <div class="flex flex-col gap-3 px-6 py-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-slate-900 truncate">{{ $p->name }}</p>
                                @if ($p->rank)
                                    <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Juara {{ $p->rank }}</span>
                                @elseif ($p->status === 'eliminated')
                                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold">Gugur</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Lolos</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">{{ $p->resident_block ?: '-' }} · {{ $p->phone_number ?: '-' }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            {{-- Round controls --}}
                            <button wire:click="promote('{{ $p->id }}')" class="text-xs px-2.5 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium" title="Naik ke babak berikutnya">Naik babak &rarr;</button>
                            @if ($round > 1)
                                <button wire:click="demote('{{ $p->id }}')" class="text-xs px-2 py-1.5 border border-slate-300 text-slate-500 rounded-md hover:bg-slate-50" title="Turun babak">&larr;</button>
                            @endif

                            {{-- Status --}}
                            @if ($p->status === 'eliminated')
                                <button wire:click="reinstate('{{ $p->id }}')" class="text-xs px-2.5 py-1.5 border border-emerald-200 text-emerald-600 rounded-md hover:bg-emerald-50 font-medium">Aktifkan</button>
                            @else
                                <button wire:click="eliminate('{{ $p->id }}')" class="text-xs px-2.5 py-1.5 border border-slate-300 text-slate-500 rounded-md hover:bg-slate-50 font-medium">Gugurkan</button>
                            @endif

                            {{-- Juara --}}
                            <span class="mx-1 h-4 w-px bg-slate-200"></span>
                            @foreach ([1, 2, 3] as $r)
                                <button wire:click="setRank('{{ $p->id }}', {{ $r }})" class="text-xs w-7 h-7 rounded-md border font-bold {{ $p->rank === $r ? 'bg-amber-400 border-amber-400 text-white' : 'border-slate-300 text-slate-500 hover:bg-amber-50' }}" title="Tetapkan Juara {{ $r }}">{{ $r }}</button>
                            @endforeach
                            @if ($p->rank)
                                <button wire:click="clearRank('{{ $p->id }}')" class="text-xs px-2 py-1.5 text-slate-400 hover:text-red-600" title="Hapus predikat juara">&times;</button>
                            @endif

                            <span class="mx-1 h-4 w-px bg-slate-200"></span>
                            <button wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus peserta ini?" class="text-xs px-2.5 py-1.5 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 text-sm">
            Belum ada peserta. Tambahkan peserta lewat form di atas.
        </div>
    @endforelse
</div>
