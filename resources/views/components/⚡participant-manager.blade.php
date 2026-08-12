<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionTeam;
use App\Models\FamilyMember;
use App\Traits\ConfirmsDeletion;

new class extends Component
{
    use ConfirmsDeletion;

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

    // Tambah tim (lomba grup): buat tim, lalu tambah anggota lintas keluarga
    public $new_team_name = '';
    public $new_team_gender = '';
    public $selectedTeamId = '';

    // Ubah nama tim yang sudah dibuat
    public ?string $editingTeamNameId = null;
    public string $edit_team_name = '';

    // Kelompokkan peserta yang sudah daftar sendiri (pool "menunggu dikelompokkan") ke tim
    public array $assignTeamSelection = [];

    // Masukkan banyak peserta sekaligus ke 1 tim (bulk assign, mempercepat pengelompokan)
    public array $bulkSelectedParticipants = [];
    public $bulkTargetTeamId = '';
    public $team_member_registration_number = '';
    public $team_member_name = '';
    public $team_member_resident_block = '';
    public $team_member_phone_number = '';
    public $team_member_age = '';

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
     * Buat tim kosong untuk lomba grup ini. Anggota ditambah belakangan satu per satu.
     */
    public function createTeam()
    {
        $data = $this->validate([
            'new_team_name' => 'nullable|string|max:255',
            'new_team_gender' => 'required|in:L,P',
        ], [
            'new_team_gender.required' => 'Pilih kategori Putra/Putri untuk tim ini.',
        ]);

        $team = CompetitionTeam::create([
            'competition_id' => $this->competitionId,
            'team_name' => $this->new_team_name ?: null,
            'gender_category' => $data['new_team_gender'],
            'round' => 1,
            'status' => 'active',
        ]);

        $this->selectedTeamId = $team->id;
        $this->success_message = $team->display_name . ' (' . $team->gender_category_label . ') berhasil dibuat. Sekarang tambahkan anggotanya.';
        $this->reset(['new_team_name', 'new_team_gender']);
    }

    /**
     * Ubah kategori putra/putri tim (mis. salah pilih saat buat tim, atau melengkapi tim lama).
     */
    public function updateTeamGender(string $id, string $gender)
    {
        if (! in_array($gender, ['L', 'P'], true)) {
            return;
        }

        $team = $this->team($id);
        $team->update(['gender_category' => $gender]);
        $this->success_message = $team->display_name . ' ditetapkan sebagai kategori ' . $team->gender_category_label . '.';
    }

    /**
     * Mulai ubah nama tim (isi input dengan nama saat ini).
     */
    public function startEditTeamName(string $id)
    {
        $team = $this->team($id);
        $this->editingTeamNameId = $team->id;
        $this->edit_team_name = $team->team_name ?? '';
    }

    /**
     * Simpan nama tim baru. Kosong = kembali ke nama otomatis ("Tim #xxxxxxxx").
     */
    public function saveTeamName()
    {
        if (! $this->editingTeamNameId) {
            return;
        }

        $data = $this->validate([
            'edit_team_name' => 'nullable|string|max:255',
        ]);

        $team = $this->team($this->editingTeamNameId);
        $team->update(['team_name' => $data['edit_team_name'] ?: null]);

        $this->success_message = 'Nama tim diubah menjadi ' . $team->display_name . '.';
        $this->reset(['editingTeamNameId', 'edit_team_name']);
    }

    public function cancelEditTeamName()
    {
        $this->reset(['editingTeamNameId', 'edit_team_name']);
    }

    protected function selectedTeam(): CompetitionTeam
    {
        return CompetitionTeam::where('competition_id', $this->competitionId)->findOrFail($this->selectedTeamId);
    }

    /**
     * Masukkan peserta yang sudah daftar sendiri (menunggu dikelompokkan) ke sebuah tim.
     */
    public function assignToTeam(string $participantId)
    {
        $teamId = $this->assignTeamSelection[$participantId] ?? '';

        if ($teamId === '') {
            $this->addError('assign_' . $participantId, 'Pilih tim tujuan terlebih dahulu.');
            return;
        }

        $participant = $this->participant($participantId);
        $team = CompetitionTeam::where('competition_id', $this->competitionId)->findOrFail($teamId);

        if ($team->gender_category !== null && $participant->gender !== null && $participant->gender !== $team->gender_category) {
            $this->addError('assign_' . $participantId, $participant->name . ' (' . $participant->gender_label . ') tidak sesuai kategori ' . $team->gender_category_label . ' pada ' . $team->display_name . '.');
            return;
        }

        if ($this->maxTeamSize !== null && $team->members()->count() >= $this->maxTeamSize) {
            $this->addError('assign_' . $participantId, $team->display_name . ' sudah mencapai maksimal ' . $this->maxTeamSize . ' anggota.');
            return;
        }

        $participant->update(['competition_team_id' => $team->id]);
        unset($this->assignTeamSelection[$participantId]);
        $this->success_message = $participant->name . ' dimasukkan ke ' . $team->display_name . '.';
    }

    /**
     * Centang semua peserta yang sedang menunggu dikelompokkan (mempercepat bulk assign).
     */
    public function selectAllUnassigned(): void
    {
        $ids = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->whereNull('competition_team_id')
            ->pluck('id');

        foreach ($ids as $id) {
            $this->bulkSelectedParticipants[$id] = true;
        }
    }

    public function clearBulkSelection(): void
    {
        $this->bulkSelectedParticipants = [];
    }

    /**
     * Masukkan semua peserta yang dicentang ke 1 tim tujuan sekaligus — mempercepat
     * pengelompokan dibanding assignToTeam() satu-satu.
     */
    public function bulkAssignToTeam(): void
    {
        $participantIds = array_keys(array_filter($this->bulkSelectedParticipants));

        if (empty($participantIds)) {
            $this->addError('bulk_assign', 'Pilih minimal 1 peserta terlebih dahulu.');
            return;
        }

        if ($this->bulkTargetTeamId === '') {
            $this->addError('bulk_assign', 'Pilih tim tujuan terlebih dahulu.');
            return;
        }

        $team = CompetitionTeam::where('competition_id', $this->competitionId)->findOrFail($this->bulkTargetTeamId);

        $assignedCount = 0;
        $skipped = [];

        foreach ($participantIds as $participantId) {
            $participant = CompetitionParticipant::where('competition_id', $this->competitionId)
                ->whereNull('competition_team_id')
                ->find($participantId);

            if (! $participant) {
                continue;
            }

            if ($team->gender_category !== null && $participant->gender !== null && $participant->gender !== $team->gender_category) {
                $skipped[] = $participant->name . ' (kategori tidak sesuai ' . $team->gender_category_label . ')';
                continue;
            }

            if ($this->maxTeamSize !== null && $team->members()->count() >= $this->maxTeamSize) {
                $skipped[] = $participant->name . ' (' . $team->display_name . ' sudah penuh)';
                continue;
            }

            $participant->update(['competition_team_id' => $team->id]);
            $assignedCount++;
        }

        $this->bulkSelectedParticipants = [];
        $this->bulkTargetTeamId = '';

        if ($assignedCount > 0) {
            $this->success_message = $assignedCount . ' peserta dimasukkan ke ' . $team->display_name . '.';
        }

        if (! empty($skipped)) {
            $this->addError('bulk_assign', 'Dilewati: ' . implode(', ', $skipped) . '.');
        }
    }

    /**
     * Kembalikan anggota tim ke pool "menunggu dikelompokkan" (koreksi salah kelompok).
     */
    public function unassignFromTeam(string $participantId)
    {
        $participant = $this->participant($participantId);
        $participant->update(['competition_team_id' => null]);
        $this->success_message = $participant->name . ' dikembalikan ke daftar menunggu dikelompokkan.';
    }

    /**
     * Tambah anggota tim lewat No. Daftar warga (boleh dari keluarga mana saja).
     */
    public function addTeamMemberByRegistration()
    {
        $this->validate([
            'selectedTeamId' => 'required',
            'team_member_registration_number' => 'required|string|max:20',
        ], [
            'selectedTeamId.required' => 'Pilih atau buat tim terlebih dahulu.',
        ]);

        $competition = Competition::findOrFail($this->competitionId);
        $team = $this->selectedTeam();

        $input = trim($this->team_member_registration_number);
        $candidates = array_values(array_unique(array_filter([
            $input,
            ctype_digit($input) ? str_pad($input, 4, '0', STR_PAD_LEFT) : null,
        ])));

        $member = FamilyMember::where('event_id', $competition->event_id)
            ->whereIn('registration_number', $candidates)
            ->first();

        if (! $member) {
            $this->addError('team_member_registration_number', 'No. Daftar tidak ditemukan untuk acara ini.');
            return;
        }

        $already = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->where('family_member_id', $member->id)
            ->exists();

        if ($already) {
            $this->addError('team_member_registration_number', $member->name . ' sudah terdaftar di lomba ini.');
            return;
        }

        $age = $member->age !== null ? (int) $member->age : null;

        if (! $competition->isAgeEligible($age)) {
            $this->addError('team_member_registration_number', 'Umur ' . $member->name . ' tidak sesuai kategori lomba ini (' . $competition->age_limit_label . ').');
            return;
        }

        if ($team->gender_category !== null && $member->gender !== null && $member->gender !== $team->gender_category) {
            $this->addError('team_member_registration_number', $member->name . ' (' . $member->gender_label . ') tidak sesuai kategori ' . $team->gender_category_label . ' pada ' . $team->display_name . '.');
            return;
        }

        if ($this->maxTeamSize !== null && $team->members()->count() >= $this->maxTeamSize) {
            $this->addError('team_member_registration_number', $team->display_name . ' sudah mencapai maksimal ' . $this->maxTeamSize . ' anggota.');
            return;
        }

        CompetitionParticipant::create([
            'competition_id' => $this->competitionId,
            'competition_team_id' => $team->id,
            'family_member_id' => $member->id,
            'name' => $member->name,
            'resident_block' => optional($member->familySubmission)->resident_block,
            'phone_number' => optional($member->familySubmission)->phone_number,
            'age' => $age,
            'round' => 1,
            'status' => 'active',
        ]);

        $this->success_message = $member->name . ' berhasil ditambahkan ke ' . $team->display_name . '.';
        $this->reset('team_member_registration_number');
    }

    /**
     * Tambah anggota tim secara manual (tamu non-warga / tidak punya No. Daftar).
     */
    public function addTeamMemberManual()
    {
        $data = $this->validate([
            'selectedTeamId' => 'required',
            'team_member_name' => 'required|string|max:255',
            'team_member_resident_block' => 'nullable|string|max:100',
            'team_member_phone_number' => 'nullable|string|max:50',
            'team_member_age' => 'nullable|integer|min:0|max:120',
        ], [
            'selectedTeamId.required' => 'Pilih atau buat tim terlebih dahulu.',
        ]);

        $competition = Competition::findOrFail($this->competitionId);
        $team = $this->selectedTeam();

        $age = $this->team_member_age === '' ? null : (int) $this->team_member_age;

        if (! $competition->isAgeEligible($age)) {
            $this->addError('team_member_age', 'Umur tidak sesuai kategori lomba ini (' . $competition->age_limit_label . ').');
            return;
        }

        if ($this->maxTeamSize !== null && $team->members()->count() >= $this->maxTeamSize) {
            $this->addError('team_member_name', $team->display_name . ' sudah mencapai maksimal ' . $this->maxTeamSize . ' anggota.');
            return;
        }

        CompetitionParticipant::create([
            'competition_id' => $this->competitionId,
            'competition_team_id' => $team->id,
            'name' => $data['team_member_name'],
            'resident_block' => $data['team_member_resident_block'],
            'phone_number' => $data['team_member_phone_number'],
            'age' => $age,
            'round' => 1,
            'status' => 'active',
        ]);

        $this->success_message = $data['team_member_name'] . ' berhasil ditambahkan ke ' . $team->display_name . '.';
        $this->reset(['team_member_name', 'team_member_resident_block', 'team_member_phone_number', 'team_member_age']);
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
        $genderCategory = $team->gender_category;

        // Satu pemegang tiap juara PER KATEGORI putra/putri — kosongkan pemegang lama di kategori yang sama saja.
        CompetitionTeam::where('competition_id', $this->competitionId)
            ->where('rank', $rank)
            ->where('id', '!=', $team->id)
            ->when(
                $genderCategory === null,
                fn ($q) => $q->whereNull('gender_category'),
                fn ($q) => $q->where('gender_category', $genderCategory),
            )
            ->update(['rank' => null]);

        $team->update(['rank' => $rank, 'status' => 'active']);
        $this->success_message = $team->display_name . ' ditetapkan sebagai Juara ' . $rank . ' (kategori ' . $team->gender_category_label . ').';
    }

    public function clearTeamRank(string $id)
    {
        $this->team($id)->update(['rank' => null]);
    }

    public function deleteTeam(string $id)
    {
        $team = $this->team($id);

        // Lepas anggotanya dulu (bukan hapus) — kolom FK cascade-delete kalau timnya langsung dihapus.
        CompetitionParticipant::where('competition_team_id', $team->id)->update(['competition_team_id' => null]);

        $team->delete();
        $this->success_message = 'Tim dihapus. Anggotanya dikembalikan ke daftar menunggu dikelompokkan.';
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
        $categoryKey = $participant->display_category_key;

        // Satu pemegang tiap juara PER KATEGORI (umur, dan untuk Dewasa juga per Putra/Putri)
        // — kosongkan pemegang lama di kategori yang sama saja.
        CompetitionParticipant::where('competition_id', $this->competitionId)
            ->where('rank', $rank)
            ->where('id', '!=', $participant->id)
            ->get()
            ->filter(fn ($p) => $p->display_category_key === $categoryKey)
            ->each(fn ($p) => $p->update(['rank' => null]));

        $participant->update(['rank' => $rank, 'status' => 'active']);
        $this->success_message = $participant->name . ' ditetapkan sebagai Juara ' . $rank . ' (kategori ' . $participant->display_category_label . ').';
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
                ->with('members')
                ->orderByDesc('round')
                ->orderBy('rank')
                ->orderBy('created_at')
                ->get();

            $unassigned = CompetitionParticipant::where('competition_id', $this->competitionId)
                ->whereNull('competition_team_id')
                ->with('familyMember:id,registration_number,gender')
                ->orderBy('name')
                ->get();

            $teamsByGender = $teams
                ->groupBy(fn ($t) => $t->gender_category ?? 'none')
                ->sortBy(fn ($group, $key) => match ($key) { 'L' => 0, 'P' => 1, default => 2 });

            $unassignedByGender = $unassigned
                ->groupBy(fn ($p) => $p->gender ?? 'none')
                ->sortBy(fn ($group, $key) => match ($key) { 'L' => 0, 'P' => 1, default => 2 });

            return [
                'teams' => $teams,
                'teamsByGender' => $teamsByGender,
                'unassigned' => $unassigned,
                'unassignedByGender' => $unassignedByGender,
                'participantsByCategory' => collect(),
                'totalParticipants' => $teams->sum(fn ($t) => $t->members->count()) + $unassigned->count(),
            ];
        }

        $participants = CompetitionParticipant::where('competition_id', $this->competitionId)
            ->with('familyMember:id,registration_number,gender')
            ->orderBy('name')
            ->get();

        $byCategory = $participants
            ->groupBy(fn ($p) => $p->display_category_key)
            ->sortBy(fn ($group, $key) => \App\Support\AgeCategory::orderForDisplayKey($key));

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
            <a href="{{ route('admin.bracket', $competitionSlug) }}" class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Bagan Turnamen</a>
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
                <h4 class="font-semibold text-slate-900">Kategori {{ $participants->first()->display_category_label }}</h4>
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

                                        <button wire:click="confirmDelete('{{ $p->id }}', 'peserta ini')" class="inline-flex h-8 items-center rounded-md border border-red-200 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
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
        <!-- Buat tim & tambah anggota (lomba grup) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span>
                Buat Tim
            </h3>
            <p class="-mt-3 mb-4 text-xs text-slate-500">
                Buat tim dulu (nama bebas), lalu tambahkan anggotanya satu per satu — anggota boleh dari keluarga/blok mana saja, tidak harus satu KK.
                @if ($teamSizeLabel)
                    Jumlah anggota tim: <span class="font-semibold">{{ $teamSizeLabel }}</span>.
                @endif
            </p>

            <form wire:submit.prevent="createTeam" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tim</label>
                        <input type="text" wire:model="new_team_name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Tim Blok A">
                        @error('new_team_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-full sm:w-40">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori</label>
                        <select wire:model="new_team_gender" data-custom-select class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">— Pilih —</option>
                            <option value="L">Putra</option>
                            <option value="P">Putri</option>
                        </select>
                        @error('new_team_gender') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="shrink-0 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm sm:mt-[22px]">Buat Tim</button>
                </div>
            </form>

            @if ($teams->isNotEmpty())
                <div class="mt-4 rounded-lg border border-slate-200 p-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tambah Anggota ke Tim</label>
                    <select wire:model="selectedTeamId" data-custom-select class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500">
                        <option value="">— Pilih tim —</option>
                        @foreach ($teams as $t)
                            @php $isFull = $maxTeamSize !== null && $t->members->count() >= $maxTeamSize; @endphp
                            <option value="{{ $t->id }}" @disabled($isFull)>{{ $t->display_name }} ({{ $t->gender_category_label }}, {{ $t->members->count() }}{{ $maxTeamSize ? '/' . $maxTeamSize : '' }} anggota){{ $isFull ? ' — PENUH' : '' }}</option>
                        @endforeach
                    </select>
                    @error('selectedTeamId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror

                    {{-- Tambah cepat via No. Daftar (ambil dari data warga, lintas keluarga) --}}
                    <form wire:submit.prevent="addTeamMemberByRegistration" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                        <div class="flex-1">
                            <input type="text" wire:model="team_member_registration_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="No. Daftar, contoh: 0001">
                            @error('team_member_registration_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="shrink-0 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Cari &amp; Tambahkan</button>
                    </form>

                    <div class="my-4 flex items-center gap-3 text-[11px] uppercase tracking-wide text-slate-400">
                        <span class="h-px flex-1 bg-slate-200"></span>
                        atau input manual (tamu non-warga)
                        <span class="h-px flex-1 bg-slate-200"></span>
                    </div>

                    <form wire:submit.prevent="addTeamMemberManual" class="grid grid-cols-1 md:grid-cols-[1.5fr_1fr_0.7fr_1fr_auto] gap-4 md:items-end">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Anggota</label>
                            <input type="text" wire:model="team_member_name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Nama anggota">
                            @error('team_member_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Blok / Asal</label>
                            <input type="text" wire:model="team_member_resident_block" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="A/12">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Umur</label>
                            <input type="number" wire:model="team_member_age" min="0" max="120" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="10">
                            @error('team_member_age') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">No. HP (opsional)</label>
                            <input type="text" wire:model="team_member_phone_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0812xxxx">
                        </div>
                        <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Tambah</button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Peserta yang daftar sendiri lewat form publik, belum masuk tim -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-3 border-b border-slate-100 bg-slate-50">
                <span class="w-2 h-4 bg-amber-500 rounded"></span>
                <h4 class="font-semibold text-slate-900">Menunggu Dikelompokkan</h4>
                <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $unassigned->count() }} peserta</span>
            </div>
            @if ($unassigned->count() > 0)
                <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-slate-100 bg-amber-50/40">
                    <button type="button" wire:click="selectAllUnassigned" class="text-xs font-medium text-red-600 hover:underline">Pilih Semua</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" wire:click="clearBulkSelection" class="text-xs font-medium text-slate-500 hover:underline">Batalkan Pilihan</button>
                    <span class="text-xs text-slate-400">({{ count(array_filter($bulkSelectedParticipants)) }} dipilih)</span>
                    <div class="ml-auto flex items-center gap-2">
                        <select wire:model="bulkTargetTeamId" data-custom-select class="w-48 px-3 py-2 border border-slate-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">— Pilih tim tujuan —</option>
                            @foreach ($teams as $t)
                                @php $isFull = $maxTeamSize !== null && $t->members->count() >= $maxTeamSize; @endphp
                                <option value="{{ $t->id }}" @disabled($isFull)>{{ $t->display_name }} ({{ $t->members->count() }}{{ $maxTeamSize ? '/' . $maxTeamSize : '' }} anggota){{ $isFull ? ' — PENUH' : '' }}</option>
                            @endforeach
                        </select>
                        <button wire:click="bulkAssignToTeam" class="shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-medium shadow-sm">Masukkan yang Dipilih</button>
                    </div>
                    @error('bulk_assign') <div class="w-full text-xs text-red-600">{{ $message }}</div> @enderror
                </div>
            @endif
            @forelse ($unassignedByGender as $genderGroup)
                @if ($unassignedByGender->count() > 1)
                    <div class="flex items-center gap-2 px-6 py-2 bg-slate-50/60 border-b border-slate-100">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $genderGroup->first()->gender_label ?? 'Gender tidak diketahui' }}</span>
                        <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $genderGroup->count() }} peserta</span>
                    </div>
                @endif
                @foreach ($genderGroup as $p)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                        <div class="flex min-w-0 items-start gap-3">
                            <input type="checkbox" wire:model="bulkSelectedParticipants.{{ $p->id }}" class="mt-1.5 h-4 w-4 shrink-0 rounded border-slate-300 text-red-600 focus:ring-red-500">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-900">
                                    {{ $p->name }}{{ $p->age !== null ? ' · ' . $p->age . ' th' : '' }}
                                    @if ($p->gender_label)
                                        <span class="ml-1 text-[10px] font-bold uppercase tracking-wide {{ $p->gender === 'L' ? 'text-blue-600' : 'text-pink-600' }}">{{ $p->gender_label }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-[11px] text-slate-400">
                                    @if ($p->familyMember?->registration_number)
                                        No. Daftar: <span class="font-semibold text-slate-500">{{ $p->familyMember->registration_number }}</span>
                                    @else
                                        Peserta manual
                                    @endif
                                    @if ($p->resident_block) · Blok {{ $p->resident_block }} @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select wire:model="assignTeamSelection.{{ $p->id }}" data-custom-select class="w-48 px-3 py-2 border border-slate-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500">
                                <option value="">— Pilih tim —</option>
                                @foreach ($teams as $t)
                                    @continue($p->gender !== null && $t->gender_category !== null && $p->gender !== $t->gender_category)
                                    @php $isFull = $maxTeamSize !== null && $t->members->count() >= $maxTeamSize; @endphp
                                    <option value="{{ $t->id }}" @disabled($isFull)>{{ $t->display_name }} ({{ $t->members->count() }}{{ $maxTeamSize ? '/' . $maxTeamSize : '' }} anggota){{ $isFull ? ' — PENUH' : '' }}</option>
                                @endforeach
                            </select>
                            <button wire:click="assignToTeam('{{ $p->id }}')" class="shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-medium shadow-sm">Masukkan ke Tim</button>
                            <button wire:click="confirmDelete('{{ $p->id }}', 'peserta ini')" class="shrink-0 px-3 py-2 border border-red-200 text-red-600 rounded-md text-xs font-medium hover:bg-red-50">Hapus</button>
                        </div>
                        @error('assign_' . $p->id) <div class="w-full text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            @empty
                <div class="p-6 text-center text-slate-400 text-sm">Belum ada peserta yang menunggu dikelompokkan.</div>
            @endforelse
        </div>

        <!-- Daftar tim, dikelompokkan per kategori Putra/Putri (fairness, mirip kategori umur) -->
        @forelse ($teamsByGender as $groupTeams)
            <div class="mb-6">
                @if ($teamsByGender->count() > 1)
                    <div class="mb-3 flex items-center gap-2">
                        <span class="w-2 h-4 bg-red-600 rounded"></span>
                        <h4 class="font-semibold text-slate-900">Kategori {{ $groupTeams->first()->gender_category_label }}</h4>
                        <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $groupTeams->count() }} tim</span>
                    </div>
                @endif

                @foreach ($groupTeams as $team)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-4 overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="min-w-0">
                                @if ($editingTeamNameId === $team->id)
                                    <form wire:submit.prevent="saveTeamName" class="flex items-center gap-1.5">
                                        <input type="text" wire:model="edit_team_name" autofocus maxlength="255" placeholder="Kosongkan untuk nama otomatis" class="w-56 max-w-full rounded-md border border-slate-300 px-2 py-1 text-sm font-semibold text-slate-900 focus:ring-1 focus:ring-red-500 focus:border-red-500">
                                        <button type="submit" class="shrink-0 rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700">Simpan</button>
                                        <button type="button" wire:click="cancelEditTeamName" class="shrink-0 rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-500 hover:bg-slate-50">Batal</button>
                                    </form>
                                    @error('edit_team_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                @else
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-slate-900 truncate">{{ $team->display_name }}</h4>
                                    <button wire:click="startEditTeamName('{{ $team->id }}')" class="shrink-0 text-xs font-medium text-slate-400 hover:text-red-600" title="Ubah nama tim">Ubah Nama</button>
                                    <span class="inline-block whitespace-nowrap text-xs px-2 py-0.5 rounded {{ $team->gender_category ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-400' }} font-semibold">{{ $team->gender_category_label }}</span>
                                    <span class="inline-block whitespace-nowrap text-xs px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-100 font-semibold">Babak {{ $team->round }}{{ $team->round == $totalRounds ? ' (Final)' : '' }}</span>
                                    @if ($team->rank)
                                        <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Juara {{ $team->rank }}</span>
                                    @elseif ($team->status === 'eliminated')
                                        <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold">Gugur</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Lolos</span>
                                    @endif
                                </div>
                                @endif
                                <p class="mt-1 text-xs text-slate-500">{{ $team->members->count() }} anggota</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kategori</span>
                                    <button wire:click="updateTeamGender('{{ $team->id }}', 'L')" class="h-6 px-2 rounded-md border text-xs font-bold {{ $team->gender_category === 'L' ? 'bg-red-600 border-red-600 text-white' : 'border-slate-300 bg-white text-slate-500 hover:bg-red-50' }}">Putra</button>
                                    <button wire:click="updateTeamGender('{{ $team->id }}', 'P')" class="h-6 px-2 rounded-md border text-xs font-bold {{ $team->gender_category === 'P' ? 'bg-red-600 border-red-600 text-white' : 'border-slate-300 bg-white text-slate-500 hover:bg-red-50' }}">Putri</button>
                                </div>

                                <span class="h-6 w-px bg-slate-200"></span>

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

                                <button wire:click="confirmDelete('{{ $team->id }}', 'tim ini (anggotanya akan dikembalikan ke Menunggu Dikelompokkan, bukan ikut terhapus)', 'deleteTeam')" class="inline-flex h-8 items-center rounded-md border border-red-200 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/60 px-6 py-3">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Anggota</p>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($team->members as $member)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-700">
                                        {{ $member->name }}{{ $member->age !== null ? ' · ' . $member->age . ' th' : '' }}
                                        <button wire:click="unassignFromTeam('{{ $member->id }}')" class="text-slate-400 hover:text-amber-600" title="Keluarkan dari tim (tetap muncul di Menunggu Dikelompokkan)"><x-icon name="arrow-uturn-left" class="h-3.5 w-3.5" /></button>
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">Belum ada anggota. Tambahkan lewat form di atas.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 text-sm">
                Belum ada tim. Daftarkan tim lewat form di atas.
            </div>
        @endforelse
    @endif

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
