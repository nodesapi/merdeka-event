<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\SiteSetting;

new class extends Component
{
    public string $competitionId;
    public string $competitionName = '';
    public ?string $siteLogoUrl = null;

    public function mount(Competition $competition)
    {
        $this->competitionId = $competition->id;
        $this->competitionName = $competition->name;
        $this->siteLogoUrl = SiteSetting::current()->logo_url;
    }

    public function with(): array
    {
        $competition = Competition::findOrFail($this->competitionId);

        $entrantsById = $competition->entrants()
            ->when($competition->isGroup(), fn ($q) => $q->with('members'))
            ->get()
            ->keyBy('id');

        $matchesByCategory = $competition->matches()
            ->with('entrants')
            ->orderBy('round')
            ->orderBy('heat_number')
            ->get()
            ->groupBy('category_key');

        $categories = $matchesByCategory->map(function ($matches) use ($entrantsById) {
            $matchesByRound = $matches->groupBy('round');
            $maxRound = $matchesByRound->keys()->max();
            $currentRoundMatches = $maxRound ? $matchesByRound->get($maxRound) : collect();
            $currentRoundMainMatches = $currentRoundMatches?->where('is_third_place', false) ?? collect();
            $championEntrant = null;

            if ($currentRoundMainMatches->count() === 1 && $currentRoundMainMatches->first()->isDecided()) {
                $championEntrant = $currentRoundMainMatches->first()->winnerEntrant($entrantsById);
            }

            $sampleEntrant = $matches->first()->resolveEntrants($entrantsById)->first();

            return [
                'label' => $sampleEntrant?->display_category_label ?? 'Umum',
                'matchesByRound' => $matchesByRound,
                'championEntrant' => $championEntrant,
            ];
        });

        return [
            'linesPerMatch' => $competition->bracket_lines_per_match,
            'entrantsById' => $entrantsById,
            'categories' => $categories,
        ];
    }
};
?>

<div class="min-h-screen bg-white px-6 py-10" wire:poll.3s>
    <div class="mx-auto max-w-[1600px]">
        <div class="mb-8 flex flex-col items-center text-center">
            @if ($siteLogoUrl)
                <img src="{{ $siteLogoUrl }}" alt="Logo" class="mb-3 h-14 w-auto object-contain">
            @endif
            <span class="merdeka-badge">Bagan Turnamen</span>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-stone-900 sm:text-4xl">{{ $competitionName }}</h1>
        </div>

        <div class="space-y-12">
            @foreach ($categories as $cat)
                <div>
                    @if ($categories->count() > 1)
                        <h2 class="mb-4 text-center text-lg font-black text-stone-800">Kategori {{ $cat['label'] }}</h2>
                    @endif

                    @if ($cat['championEntrant'])
                        <div class="mx-auto mb-6 max-w-md rounded-2xl border-2 border-amber-300 bg-amber-50 p-6 text-center shadow-sm">
                            <x-icon name="trophy" class="mx-auto h-12 w-12 text-amber-500" />
                            <p class="mt-2 text-xs font-bold uppercase tracking-widest text-amber-700">Juara {{ $categories->count() > 1 ? 'Kategori' : 'Turnamen' }}</p>
                            <p class="mt-1 text-2xl font-black text-stone-900">{{ $cat['championEntrant']->display_name }}</p>
                        </div>
                    @endif

                    <x-bracket-view
                        :matches-by-round="$cat['matchesByRound']"
                        :entrants-by-id="$entrantsById"
                        :lines-per-match="$linesPerMatch"
                        :interactive="false"
                    />
                </div>
            @endforeach
        </div>
    </div>
</div>
