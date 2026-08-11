<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Support\BracketGenerator;
use App\Traits\ConfirmsDeletion;

new class extends Component
{
    use ConfirmsDeletion;

    public string $competitionId;
    public string $competitionName = '';
    public string $competitionSlug = '';
    public bool $isGroup = false;

    public $lines_per_match = 2;

    public $success_message = '';
    public $error_message = '';

    public function mount(Competition $competition)
    {
        $this->competitionId = $competition->id;
        $this->competitionName = $competition->name;
        $this->competitionSlug = $competition->slug;
        $this->isGroup = $competition->isGroup();
    }

    protected function competition(): Competition
    {
        return Competition::findOrFail($this->competitionId);
    }

    public function startBracket()
    {
        $this->validate([
            'lines_per_match' => 'required|integer|min:2|max:64',
        ]);

        try {
            (new BracketGenerator($this->competition()))->start((int) $this->lines_per_match);
            $this->success_message = 'Bagan berhasil dibuat.';
            $this->error_message = '';
        } catch (\InvalidArgumentException $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function decideWinner(string $matchId, string $winnerEntrantId)
    {
        try {
            $match = CompetitionMatch::where('competition_id', $this->competitionId)->findOrFail($matchId);
            (new BracketGenerator($this->competition()))->decideMatch($match, $winnerEntrantId);
            $this->success_message = '';
            $this->error_message = '';
        } catch (\InvalidArgumentException $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function undoMatch(string $matchId)
    {
        try {
            $match = CompetitionMatch::where('competition_id', $this->competitionId)->findOrFail($matchId);
            (new BracketGenerator($this->competition()))->undoMatch($match);
            $this->success_message = '';
            $this->error_message = '';
        } catch (\InvalidArgumentException $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function nextRound(string $categoryKey)
    {
        try {
            (new BracketGenerator($this->competition()))->nextRound($categoryKey);
            $this->success_message = 'Babak berikutnya berhasil dibuat.';
            $this->error_message = '';
        } catch (\InvalidArgumentException $e) {
            $this->error_message = $e->getMessage();
        }
    }

    /**
     * Dipanggil dari modal konfirmasi (ConfirmsDeletion) — parameter $id diabaikan,
     * aksinya selalu untuk bagan lomba ini ($this->competitionId), semua kategori.
     */
    public function resetBracket(string $id)
    {
        (new BracketGenerator($this->competition()))->reset();
        $this->success_message = 'Bagan dihapus. Peserta/tim dikembalikan ke babak 1.';
        $this->error_message = '';
    }

    public function dismissAlert()
    {
        $this->success_message = '';
        $this->error_message = '';
    }

    public function with(): array
    {
        $competition = $this->competition();
        $hasBracket = $competition->hasBracket();

        $entrantsById = $competition->entrants()
            ->when($competition->isGroup(), fn ($q) => $q->with('members'))
            ->get()
            ->keyBy('id');

        $entrantsByCategory = $competition->entrantsByCategory();

        $matchesByCategory = $competition->matches()
            ->with('entrants')
            ->orderBy('round')
            ->orderBy('heat_number')
            ->get()
            ->groupBy('category_key');

        $categories = $entrantsByCategory->map(function ($categoryEntrants, $categoryKey) use ($matchesByCategory, $entrantsById) {
            $matchesByRound = ($matchesByCategory->get($categoryKey) ?? collect())->groupBy('round');
            $maxRound = $matchesByRound->keys()->max();
            $currentRoundMatches = $maxRound ? $matchesByRound->get($maxRound) : collect();
            $currentRoundMainMatches = $currentRoundMatches?->where('is_third_place', false) ?? collect();
            $isFinalRound = $currentRoundMainMatches->count() === 1;
            $championEntrant = null;

            if ($isFinalRound && $currentRoundMainMatches->first()?->isDecided()) {
                $championEntrant = $currentRoundMainMatches->first()->winnerEntrant($entrantsById);
            }

            $canGoNextRound = $currentRoundMatches !== null
                && $currentRoundMatches->isNotEmpty()
                && ! $isFinalRound
                && $currentRoundMatches->every(fn (CompetitionMatch $m) => $m->isDecided());

            return [
                'label' => $categoryEntrants->first()->display_category_label,
                'entrantCount' => $categoryEntrants->count(),
                'matchesByRound' => $matchesByRound,
                'hasMatches' => $matchesByRound->isNotEmpty(),
                'canGoNextRound' => $canGoNextRound,
                'championEntrant' => $championEntrant,
            ];
        });

        return [
            'competitionType' => $competition->isGroup() ? 'group' : 'individual',
            'hasBracket' => $hasBracket,
            'linesPerMatch' => $competition->bracket_lines_per_match,
            'entrantCount' => $entrantsById->count(),
            'categories' => $categories,
            'entrantsById' => $entrantsById,
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
    @if ($error_message)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center justify-between shadow-sm">
            <span class="font-medium text-sm">{{ $error_message }}</span>
            <button wire:click="dismissAlert" class="text-red-500 hover:text-red-800 font-bold">&times;</button>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.participants', $competitionSlug) }}" class="text-xs font-semibold text-red-600 hover:underline">&larr; Kembali ke Peserta &amp; Juara</a>
            <h3 class="mt-1 text-xl font-bold text-slate-900">Bagan Turnamen — {{ $competitionName }}</h3>
            <p class="text-sm text-slate-500">{{ $entrantCount }} {{ $competitionType === 'group' ? 'tim' : 'peserta' }} terdaftar, dikelompokkan per kategori sama seperti layar Peserta &amp; Juara.</p>
        </div>
        @if ($hasBracket)
            <div class="flex items-center gap-2">
                <a href="{{ route('public.competition.bracket', $competitionSlug) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                    <x-icon name="expand" class="h-4 w-4" /> Buka Layar Bagan
                </a>
                <button wire:click="confirmDelete('{{ $competitionId }}', 'bagan turnamen ini (semua kategori) — semua heat dihapus dan peserta/tim dikembalikan ke babak 1', 'resetBracket')" class="text-xs px-3 py-2 border border-red-200 text-red-600 rounded-md hover:bg-red-50 font-medium">Hapus Bagan</button>
            </div>
        @endif
    </div>

    @if (! $hasBracket)
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-xl">
            <h4 class="font-semibold text-base text-slate-900 mb-1">Buat Bagan</h4>
            <p class="text-sm text-slate-500 mb-4">Tentukan berapa peserta/tim tanding sekaligus dalam satu pertandingan. Isi 2 untuk model "vs" head-to-head (mis. Tarik Tambang), atau lebih dari 2 untuk beberapa line sekaligus (mis. Lomba Kelereng 5 line). Setiap kategori dapat bagan sendiri-sendiri.</p>

            <div class="mb-4 space-y-1.5">
                @foreach ($categories as $cat)
                    <div class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                        <span class="font-semibold text-slate-700">{{ $cat['label'] }}</span>
                        <span class="{{ $cat['entrantCount'] >= 2 ? 'text-slate-500' : 'text-amber-600' }}">{{ $cat['entrantCount'] }} {{ $competitionType === 'group' ? 'tim' : 'peserta' }}{{ $cat['entrantCount'] < 2 ? ' — tidak cukup, dilewati' : '' }}</span>
                    </div>
                @endforeach
            </div>

            @if ($entrantCount < 2)
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Minimal 2 {{ $competitionType === 'group' ? 'tim' : 'peserta' }} dalam satu kategori untuk membuat bagan.</p>
            @else
                <form wire:submit.prevent="startBracket" class="flex items-end gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Peserta/Tim per Pertandingan</label>
                        <input type="number" wire:model="lines_per_match" min="2" max="64" class="w-40 px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500">
                        @error('lines_per_match') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Buat Bagan</button>
                </form>
            @endif
        </div>
    @else
        <div class="space-y-10">
            @foreach ($categories as $categoryKey => $cat)
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="w-2 h-4 bg-red-600 rounded"></span>
                        <h4 class="font-semibold text-slate-900">Kategori {{ $cat['label'] }}</h4>
                        <span class="text-xs px-2 py-0.5 bg-white text-slate-600 rounded border border-slate-200">{{ $cat['entrantCount'] }} {{ $competitionType === 'group' ? 'tim' : 'peserta' }}</span>
                    </div>

                    @if (! $cat['hasMatches'])
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Tidak cukup peserta di kategori ini (minimal 2) — bagan dilewati saat dibuat.</p>
                    @else
                        @if ($cat['championEntrant'])
                            <div class="mb-4 rounded-xl border-2 border-amber-300 bg-gradient-to-b from-amber-50 to-white p-6 text-center shadow-sm">
                                <x-icon name="trophy" class="mx-auto h-10 w-10 text-amber-500" />
                                <p class="mt-2 text-xs font-bold uppercase tracking-widest text-amber-600">Juara Kategori {{ $cat['label'] }}</p>
                                <p class="mt-1 text-xl font-black text-slate-900">{{ $cat['championEntrant']->display_name }}</p>
                            </div>
                        @elseif ($cat['canGoNextRound'])
                            <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-semibold text-emerald-800">Semua heat di babak ini sudah ada pemenangnya.</p>
                                <button wire:click="nextRound('{{ $categoryKey }}')" class="shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm">Buat Babak Berikutnya &rarr;</button>
                            </div>
                        @endif

                        <x-bracket-view
                            :matches-by-round="$cat['matchesByRound']"
                            :entrants-by-id="$entrantsById"
                            :lines-per-match="$linesPerMatch"
                            :interactive="true"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
