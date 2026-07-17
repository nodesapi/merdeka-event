<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionTeam;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $competitionId;
    public string $competitionName = '';
    public string $competitionSlug = '';
    public int $totalRounds = 1;
    public bool $isGroup = false;
    public ?int $minTeamMembers = null;
    public ?int $maxTeamSize = null;
    public ?string $teamSizeLabel = null;

    // Quick add via No. Daftar (data warga)
    public $registration_number = '';

    // New participant form (manual)
    public $name = '';
    public $resident_block = '';
    public $phone_number = '';
    public $age = '';

    // Tambah tim (lomba grup): cari keluarga via No. Daftar salah satu anggota
    public $team_registration_number = '';
    public $team_name = '';
    public $foundFamily = null;
    public $selectedMemberIds = [];

    public $success_message = '';

    public function mount(Competition $competition)
    {
        $this->competitionId = $competition->id;
        $this->competitionName = $competition->name;
        $this->competitionSlug = $competition->slug;
        $this->totalRounds = $competition->total_rounds;
        $this->isGroup = $competition->isGroup();
        $this->minTeamMembers = $competition->min_team_members;
        $this->maxTeamSize = $competition->max_team_size;
        $this->teamSizeLabel = $competition->team_size_label;
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
            'age' => 'nullable|integer|min:0|max:120',
        ]);

        CompetitionParticipant::create(array_merge($data, [
            'competition_id' => $this->competitionId,
            'age' => $this->age === '' ? null : (int) $this->age,
            'round' => 1,
            'status' => 'active',
        ]));

        $this->success_message = 'Peserta "' . $this->name . '" berhasil ditambahkan.';
        $this->reset(['name', 'resident_block', 'phone_number', 'age']);
    }

    public function addByRegistration()
    {
        $this->validate([
            'registration_number' => 'required|string|max:20',
        ]);

        $competition = Competition::findOrFail($this->competitionId);

        // Terima "2" maupun "0002": kalau angka, cocokkan juga versi 4 digit berpadding.
        $input = trim($this->registration_number);
        $candidates = array_values(array_unique(array_filter([
            $input,
            ctype_digit($input) ? str_pad($input, 4, '0', STR_PAD_LEFT) : null,
        ])));

        $member = FamilyMember::where('event_id', $competition->event_id)
            ->whereIn('registration_number', $candidates)
            ->first();

        if (! $member) {
            $this->addError('registration_number', 'No. Daftar tidak ditemukan untuk acara ini.');
            return;
        }

        $already = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->where('family_member_id', $member->id)
            ->exists();

        if ($already) {
            $this->addError('registration_number', $member->name . ' sudah terdaftar di lomba ini.');
            return;
        }

        $age = $member->age !== null ? (int) $member->age : null;

        if (! $competition->isAgeEligible($age)) {
            $this->addError('registration_number', 'Umur ' . $member->name . ' tidak sesuai kategori lomba ini (' . $competition->age_limit_label . ').');
            return;
        }

        CompetitionParticipant::create([
            'competition_id' => $this->competitionId,
            'family_member_id' => $member->id,
            'name' => $member->name,
            'resident_block' => optional($member->familySubmission)->resident_block,
            'phone_number' => optional($member->familySubmission)->phone_number,
            'age' => $age,
            'round' => 1,
            'status' => 'active',
        ]);

        $this->success_message = $member->name . ' (No. Daftar ' . $member->registration_number . ') berhasil didaftarkan ke lomba ini.';
        $this->reset('registration_number');
    }

    /**
     * Cari keluarga (lomba grup) lewat No. Daftar salah satu anggotanya.
     */
    public function lookupTeamFamily()
    {
        $this->validate([
            'team_registration_number' => 'required|string|max:20',
        ]);

        $competition = Competition::findOrFail($this->competitionId);

        $input = trim($this->team_registration_number);
        $candidates = array_values(array_unique(array_filter([
            $input,
            ctype_digit($input) ? str_pad($input, 4, '0', STR_PAD_LEFT) : null,
        ])));

        $member = FamilyMember::where('event_id', $competition->event_id)
            ->whereIn('registration_number', $candidates)
            ->first();

        if (! $member || ! $member->familySubmission) {
            $this->addError('team_registration_number', 'No. Daftar tidak ditemukan untuk acara ini.');
            return;
        }

        $familySubmission = $member->familySubmission;

        $alreadyTeam = CompetitionTeam::where('competition_id', $this->competitionId)
            ->where('family_submission_id', $familySubmission->id)
            ->exists();

        if ($alreadyTeam) {
            $this->addError('team_registration_number', 'Keluarga ini sudah punya tim di lomba ini.');
            return;
        }

        $members = $familySubmission->familyMembers()->orderBy('name')->get()->map(function ($m) use ($competition) {
            $age = $m->age !== null ? (int) $m->age : null;

            return [
                'id' => $m->id,
                'name' => $m->name,
                'age' => $age,
                'registration_number' => $m->registration_number,
                'eligible' => $competition->isAgeEligible($age),
                'already' => CompetitionParticipant::where('competition_id', $this->competitionId)
                    ->where('family_member_id', $m->id)
                    ->exists(),
            ];
        })->values()->all();

        $this->foundFamily = [
            'family_submission_id' => $familySubmission->id,
            'head_of_family_name' => $familySubmission->head_of_family_name,
            'members' => $members,
        ];
        $this->selectedMemberIds = [];
        $this->team_name = 'Tim Keluarga ' . $familySubmission->head_of_family_name;
    }

    /**
     * Daftarkan tim (anggota terpilih) untuk lomba grup ini.
     */
    public function createTeam()
    {
        if (! $this->foundFamily) {
            $this->addError('team_registration_number', 'Cari keluarga terlebih dahulu.');
            return;
        }

        $competition = Competition::findOrFail($this->competitionId);

        $memberIds = collect($this->selectedMemberIds)->map(fn ($id) => (string) $id)->values()->all();

        if (empty($memberIds)) {
            $this->addError('selectedMemberIds', 'Pilih minimal satu anggota tim.');
            return;
        }

        if (! $competition->isTeamSizeEligible(count($memberIds))) {
            $this->addError('selectedMemberIds', 'Jumlah anggota tidak sesuai (' . $competition->team_size_label . ').');
            return;
        }

        $selectedMembers = collect($this->foundFamily['members'])->whereIn('id', $memberIds)->values();

        foreach ($selectedMembers as $m) {
            if (! $m['eligible']) {
                $this->addError('selectedMemberIds', 'Umur ' . $m['name'] . ' tidak sesuai kategori lomba ini (' . $competition->age_limit_label . ').');
                return;
            }
            if ($m['already']) {
                $this->addError('selectedMemberIds', $m['name'] . ' sudah terdaftar di lomba ini.');
                return;
            }
        }

        $alreadyTeam = CompetitionTeam::where('competition_id', $this->competitionId)
            ->where('family_submission_id', $this->foundFamily['family_submission_id'])
            ->exists();

        if ($alreadyTeam) {
            $this->addError('team_registration_number', 'Keluarga ini sudah punya tim di lomba ini.');
            return;
        }

        $teamName = $this->team_name;

        DB::transaction(function () use ($selectedMembers, $teamName) {
            $team = CompetitionTeam::create([
                'competition_id' => $this->competitionId,
                'family_submission_id' => $this->foundFamily['family_submission_id'],
                'team_name' => $teamName ?: null,
                'round' => 1,
                'status' => 'active',
            ]);

            foreach ($selectedMembers as $m) {
                CompetitionParticipant::create([
                    'competition_id' => $this->competitionId,
                    'competition_team_id' => $team->id,
                    'family_member_id' => $m['id'],
                    'name' => $m['name'],
                    'age' => $m['age'],
                    'round' => 1,
                    'status' => 'active',
                ]);
            }
        });

        $this->success_message = 'Tim "' . ($teamName ?: 'tanpa nama') . '" berhasil didaftarkan dengan ' . $selectedMembers->count() . ' anggota.';
        $this->reset(['team_registration_number', 'team_name', 'foundFamily', 'selectedMemberIds']);
    }

    protected function team(string $id): CompetitionTeam
    {
        return CompetitionTeam::where('competition_id', $this->competitionId)->findOrFail($id);
    }

    public function promoteTeam(string $id)
    {
        $team = $this->team($id);

        if ($team->status === 'eliminated') {
            $this->success_message = 'Tim sudah gugur. Aktifkan kembali sebelum menaikkan babak.';
            return;
        }

        if ($team->round >= $this->totalRounds) {
            $this->success_message = $team->display_name . ' sudah berada di babak final (babak ' . $this->totalRounds . ').';
            return;
        }

        $team->increment('round');
        $this->success_message = $team->display_name . ' naik ke babak ' . $team->round . '.';
    }

    public function demoteTeam(string $id)
    {
        $team = $this->team($id);
        if ($team->round > 1) {
            $team->decrement('round');
        }
    }

    public function eliminateTeam(string $id)
    {
        $team = $this->team($id);
        $team->update(['status' => 'eliminated', 'rank' => null]);
        $this->success_message = $team->display_name . ' ditandai gugur.';
    }

    public function reinstateTeam(string $id)
    {
        $this->team($id)->update(['status' => 'active']);
    }

    public function setTeamRank(string $id, $rank)
    {
        $rank = (int) $rank;
        $team = $this->team($id);

        CompetitionTeam::where('competition_id', $this->competitionId)
            ->where('rank', $rank)
            ->where('id', '!=', $team->id)
            ->update(['rank' => null]);

        $team->update(['rank' => $rank, 'status' => 'active']);
        $this->success_message = $team->display_name . ' ditetapkan sebagai Juara ' . $rank . '.';
    }

    public function clearTeamRank(string $id)
    {
        $this->team($id)->update(['rank' => null]);
    }

    public function deleteTeam(string $id)
    {
        $this->team($id)->delete();
        $this->success_message = 'Tim dihapus.';
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
        $categoryKey = $participant->age_category_key;

        // Satu pemegang tiap juara PER KATEGORI umur — kosongkan pemegang lama di kategori yang sama saja.
        CompetitionParticipant::where('competition_id', $this->competitionId)
            ->where('rank', $rank)
            ->where('id', '!=', $participant->id)
            ->get()
            ->filter(fn ($p) => $p->age_category_key === $categoryKey)
            ->each(fn ($p) => $p->update(['rank' => null]));

        $participant->update(['rank' => $rank, 'status' => 'active']);
        $this->success_message = $participant->name . ' ditetapkan sebagai Juara ' . $rank . ' (kategori ' . $participant->age_category_label . ').';
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
        if ($this->isGroup) {
            $teams = CompetitionTeam::where('competition_id', $this->competitionId)
                ->with(['members', 'familySubmission:id,head_of_family_name,resident_block'])
                ->orderByDesc('round')
                ->orderBy('rank')
                ->orderBy('created_at')
                ->get();

            return [
                'teams' => $teams,
                'participantsByCategory' => collect(),
                'totalParticipants' => $teams->sum(fn ($t) => $t->members->count()),
            ];
        }

        $participants = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->with('familyMember:id,registration_number')
            ->orderBy('name')
            ->get();

        $byCategory = $participants
            ->groupBy(fn ($p) => $p->age_category_key ?? 'none')
            ->sortBy(fn ($group, $key) => \App\Support\AgeCategory::order($key === 'none' ? null : $key));

        return [
            'teams' => collect(),
            'participantsByCategory' => $byCategory,
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
            <p class="text-sm text-slate-500">
                @if ($isGroup)
                    {{ $teams->count() }} tim · {{ $totalParticipants }} peserta · {{ $totalRounds }} babak
                @else
                    {{ $totalParticipants }} peserta · {{ $totalRounds }} babak
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.participants.export', ['lomba' => $competitionSlug, 'format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                <x-icon name="wallet" class="h-4 w-4" /> Excel
            </a>
            <a href="{{ route('admin.participants.export', ['lomba' => $competitionSlug, 'format' => 'pdf']) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                <x-icon name="calendar" class="h-4 w-4" /> PDF
            </a>
            <a href="{{ route('public.competition.show', $competitionSlug) }}" target="_blank" class="text-xs px-3 py-2 border border-slate-300 text-slate-600 rounded-md hover:bg-slate-50 font-medium">Lihat halaman publik &rarr;</a>
        </div>
    </div>

    @if (! $isGroup)
        <!-- Add participant -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                Tambah Peserta
            </h3>
            <p class="-mt-3 mb-4 text-xs text-slate-500">Umumnya peserta mendaftar sendiri lewat halaman Daftar Lomba. Cara tercepat menambah manual: cukup masukkan <span class="font-semibold">No. Daftar</span> warga — nama, umur &amp; blok terisi otomatis.</p>

            {{-- Daftar cepat via No. Daftar (ambil dari data warga) --}}
            <form wire:submit.prevent="addByRegistration" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Daftar via No. Daftar</label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <input type="text" wire:model="registration_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: 0001">
                        @error('registration_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">Nama, umur &amp; blok otomatis dari data warga; umur dicek sesuai kategori lomba.</p>
                    </div>
                    <button type="submit" class="shrink-0 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Cari &amp; Daftarkan</button>
                </div>
            </form>

            <div class="my-4 flex items-center gap-3 text-[11px] uppercase tracking-wide text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span>
                atau input manual (regu / tamu non-warga)
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <form wire:submit.prevent="addParticipant" class="grid grid-cols-1 md:grid-cols-[1.5fr_1fr_0.7fr_1fr_auto] gap-4 md:items-end">
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
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Umur</label>
                    <input type="number" wire:model="age" min="0" max="120" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="10">
                    @error('age') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">No. HP (opsional)</label>
                    <input type="text" wire:model="phone_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0812xxxx">
                    @error('phone_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Tambah</button>
            </form>
        </div>

        <!-- Peserta dikelompokkan otomatis per kategori umur (fairness) -->
        @forelse ($participantsByCategory as $categoryKey => $participants)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-3 border-b border-slate-100 bg-slate-50">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                <h4 class="font-semibold text-slate-900">Kategori {{ $participants->first()->age_category_label }}</h4>
                <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $participants->count() }} peserta</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-2.5 w-10">No</th>
                            <th class="px-4 py-2.5">Nama / No. Daftar</th>
                            <th class="px-4 py-2.5 w-16">Umur</th>
                            <th class="px-4 py-2.5 w-32">Babak</th>
                            <th class="px-4 py-2.5 w-24">Status</th>
                            <th class="px-4 py-2.5">Blok / HP</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($participants as $i => $p)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $p->name }}</div>
                                    @if ($p->familyMember?->registration_number)
                                        <div class="mt-0.5 text-[11px] text-slate-400">No. Daftar: <span class="font-semibold text-slate-500">{{ $p->familyMember->registration_number }}</span></div>
                                    @else
                                        <div class="mt-0.5 text-[11px] text-slate-300">Peserta manual</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $p->age !== null ? $p->age . ' th' : '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block whitespace-nowrap text-xs px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-100 font-semibold">Babak {{ $p->round }}{{ $p->round == $totalRounds ? ' (Final)' : '' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($p->rank)
                                        <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Juara {{ $p->rank }}</span>
                                    @elseif ($p->status === 'eliminated')
                                        <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold">Gugur</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Lolos</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $p->resident_block ?: '-' }} · {{ $p->phone_number ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        {{-- Babak --}}
                                        <div class="inline-flex items-center gap-1.5">
                                            <button wire:click="promote('{{ $p->id }}')" class="inline-flex h-8 items-center rounded-md bg-red-600 px-2.5 text-xs font-medium text-white hover:bg-red-700" title="Naik ke babak berikutnya">Naik babak &rarr;</button>
                                            @if ($p->round > 1)
                                                <button wire:click="demote('{{ $p->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-500 hover:bg-slate-50" title="Turun babak">&larr;</button>
                                            @else
                                                <span class="inline-block h-8 w-8" aria-hidden="true"></span>
                                            @endif
                                        </div>

                                        <span class="h-6 w-px bg-slate-200"></span>

                                        {{-- Status --}}
                                        @if ($p->status === 'eliminated')
                                            <button wire:click="reinstate('{{ $p->id }}')" class="inline-flex h-8 w-24 items-center justify-center rounded-md border border-emerald-200 text-xs font-medium text-emerald-600 hover:bg-emerald-50">Aktifkan</button>
                                        @else
                                            <button wire:click="eliminate('{{ $p->id }}')" class="inline-flex h-8 w-24 items-center justify-center rounded-md border border-slate-300 text-xs font-medium text-slate-500 hover:bg-slate-50">Gugurkan</button>
                                        @endif

                                        <span class="h-6 w-px bg-slate-200"></span>

                                        {{-- Juara --}}
                                        <div class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2">
                                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Juara</span>
                                            @foreach ([1, 2, 3] as $r)
                                                <button wire:click="setRank('{{ $p->id }}', {{ $r }})" class="h-6 w-6 rounded-md border text-xs font-bold {{ $p->rank === $r ? 'bg-amber-400 border-amber-400 text-white' : 'border-slate-300 bg-white text-slate-500 hover:bg-amber-50' }}" title="Tetapkan Juara {{ $r }}">{{ $r }}</button>
                                            @endforeach
                                            @if ($p->rank)
                                                <button wire:click="clearRank('{{ $p->id }}')" class="px-0.5 text-slate-400 hover:text-red-600" title="Hapus predikat juara">&times;</button>
                                            @endif
                                        </div>

                                        <span class="h-6 w-px bg-slate-200"></span>

                                        <button wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus peserta ini?" class="inline-flex h-8 items-center rounded-md border border-red-200 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 text-sm">
            Belum ada peserta. Tambahkan peserta lewat form di atas.
        </div>
    @endforelse
    @else
        <!-- Tambah tim (lomba grup) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                Tambah Tim
            </h3>
            <p class="-mt-3 mb-4 text-xs text-slate-500">
                Cari keluarga lewat <span class="font-semibold">No. Daftar</span> salah satu anggotanya, lalu pilih siapa saja yang ikut dalam tim.
                @if ($teamSizeLabel)
                    Jumlah anggota tim: <span class="font-semibold">{{ $teamSizeLabel }}</span>.
                @endif
            </p>

            <form wire:submit.prevent="lookupTeamFamily" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cari Keluarga via No. Daftar</label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <input type="text" wire:model="team_registration_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: 0001">
                        @error('team_registration_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="shrink-0 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Cari Keluarga</button>
                </div>
            </form>

            @if ($foundFamily)
                <form wire:submit.prevent="createTeam" class="mt-4 rounded-lg border border-slate-200 p-4">
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Tim</label>
                        <input type="text" wire:model="team_name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Nama tim">
                    </div>

                    <p class="mb-2 text-xs font-semibold text-slate-700">Pilih Anggota Tim</p>
                    <div class="space-y-1.5">
                        @foreach ($foundFamily['members'] as $m)
                            <label class="flex items-center gap-2.5 rounded-md border border-slate-200 px-3 py-2 text-sm {{ (! $m['eligible'] || $m['already']) ? 'opacity-50' : 'hover:bg-slate-50' }}">
                                <input type="checkbox" wire:model="selectedMemberIds" value="{{ $m['id'] }}" {{ (! $m['eligible'] || $m['already']) ? 'disabled' : '' }} class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                <span class="flex-1">
                                    <span class="font-medium text-slate-900">{{ $m['name'] }}</span>
                                    <span class="text-slate-400"> · {{ $m['age'] !== null ? $m['age'] . ' th' : 'umur -' }}</span>
                                </span>
                                @if ($m['already'])
                                    <span class="text-[11px] font-semibold text-slate-400">Sudah terdaftar</span>
                                @elseif (! $m['eligible'])
                                    <span class="text-[11px] font-semibold text-red-500">Umur tidak sesuai</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('selectedMemberIds') <span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span> @enderror

                    <button type="submit" class="mt-4 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Daftarkan Tim</button>
                </form>
            @endif
        </div>

        <!-- Daftar tim -->
        @forelse ($teams as $team)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-4 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-slate-900 truncate">{{ $team->display_name }}</h4>
                            <span class="inline-block whitespace-nowrap text-xs px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-100 font-semibold">Babak {{ $team->round }}{{ $team->round == $totalRounds ? ' (Final)' : '' }}</span>
                            @if ($team->rank)
                                <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Juara {{ $team->rank }}</span>
                            @elseif ($team->status === 'eliminated')
                                <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold">Gugur</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Lolos</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $team->familySubmission?->resident_block ? 'Blok ' . $team->familySubmission->resident_block . ' · ' : '' }}
                            {{ $team->members->pluck('name')->join(', ') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center gap-1.5">
                            <button wire:click="promoteTeam('{{ $team->id }}')" class="inline-flex h-8 items-center rounded-md bg-red-600 px-2.5 text-xs font-medium text-white hover:bg-red-700" title="Naik ke babak berikutnya">Naik babak &rarr;</button>
                            @if ($team->round > 1)
                                <button wire:click="demoteTeam('{{ $team->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-500 hover:bg-slate-50" title="Turun babak">&larr;</button>
                            @endif
                        </div>

                        <span class="h-6 w-px bg-slate-200"></span>

                        @if ($team->status === 'eliminated')
                            <button wire:click="reinstateTeam('{{ $team->id }}')" class="inline-flex h-8 w-24 items-center justify-center rounded-md border border-emerald-200 text-xs font-medium text-emerald-600 hover:bg-emerald-50">Aktifkan</button>
                        @else
                            <button wire:click="eliminateTeam('{{ $team->id }}')" class="inline-flex h-8 w-24 items-center justify-center rounded-md border border-slate-300 text-xs font-medium text-slate-500 hover:bg-slate-50">Gugurkan</button>
                        @endif

                        <span class="h-6 w-px bg-slate-200"></span>

                        <div class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Juara</span>
                            @foreach ([1, 2, 3] as $r)
                                <button wire:click="setTeamRank('{{ $team->id }}', {{ $r }})" class="h-6 w-6 rounded-md border text-xs font-bold {{ $team->rank === $r ? 'bg-amber-400 border-amber-400 text-white' : 'border-slate-300 bg-white text-slate-500 hover:bg-amber-50' }}" title="Tetapkan Juara {{ $r }}">{{ $r }}</button>
                            @endforeach
                            @if ($team->rank)
                                <button wire:click="clearTeamRank('{{ $team->id }}')" class="px-0.5 text-slate-400 hover:text-red-600" title="Hapus predikat juara">&times;</button>
                            @endif
                        </div>

                        <span class="h-6 w-px bg-slate-200"></span>

                        <button wire:click="deleteTeam('{{ $team->id }}')" wire:confirm="Hapus tim ini beserta anggotanya?" class="inline-flex h-8 items-center rounded-md border border-red-200 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 text-sm">
                Belum ada tim. Daftarkan tim lewat form di atas.
            </div>
        @endforelse
    @endif
</div>
