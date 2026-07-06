<?php

namespace App\Http\Controllers;

use App\Models\ContributionItem;
use App\Models\CommitteeMember;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicController extends Controller
{
    protected function normalizeMoneyInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    /**
     * Resolve the currently active event (shared by every public page).
     */
    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    public function home(): View
    {
        $event = $this->activeEvent();

        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->sum('amount');

        $competitions = $event
            ? $event->competitions()->where('status', 'published')->withCount('participants')->get()
            : collect();

        $winners = $event
            ? CompetitionParticipant::whereNotNull('rank')
                ->whereHas('competition', fn ($q) => $q->where('event_id', $event->id)->where('status', 'published'))
                ->with('competition:id,name,slug')
                ->orderBy('rank')
                ->latest('updated_at')
                ->take(6)
                ->get()
            : collect();

        return view('public.home', [
            'event' => $event,
            'committeeCount' => $event ? $event->committeeMembers()->where('is_active', true)->count() : 0,
            'competitions' => $competitions,
            'winners' => $winners,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ]);
    }

    public function committee(): View
    {
        $event = $this->activeEvent();

        $committee = $event
            ? $event->committeeMembers()->where('is_active', true)->orderBy('level')->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('public.committee', [
            'event' => $event,
            'committee' => $committee,
        ]);
    }

    public function competitions(): View
    {
        $event = $this->activeEvent();

        $competitions = $event
            ? $event->competitions()->where('status', 'published')->withCount('participants')->orderBy('name')->get()
            : collect();

        return view('public.competitions', [
            'event' => $event,
            'competitions' => $competitions,
        ]);
    }

    public function competitionShow(Competition $competition): View
    {
        $competition->load(['event', 'participants' => function ($query) {
            $query->orderByDesc('round')->orderBy('rank')->orderBy('name');
        }]);

        $participantsByRound = $competition->participants
            ->sortByDesc('round')
            ->groupBy('round');

        $winners = $competition->participants
            ->whereNotNull('rank')
            ->sortBy('rank')
            ->values();

        return view('public.competition-show', [
            'event' => $competition->event,
            'competition' => $competition,
            'participantsByRound' => $participantsByRound,
            'winners' => $winners,
        ]);
    }

    public function finance(): View
    {
        $event = $this->activeEvent();

        $incomeTransactions = Transaction::with('user')->where('type', 'income')->latest()->get();
        $expenseTransactions = Transaction::with('user')->where('type', 'expense')->latest()->get();

        $totalIncome = (float) $incomeTransactions->sum('amount');
        $totalExpense = (float) $expenseTransactions->sum('amount');

        return view('public.finance', [
            'event' => $event,
            'incomeTransactions' => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ]);
    }

    public function familyForm(): View
    {
        $event = $this->activeEvent();

        $competitions = $event
            ? $event->competitions()->where('status', 'published')->orderBy('name')->get()
            : collect();

        return view('public.family-form', [
            'event' => $event,
            'competitions' => $competitions,
        ]);
    }

    public function storeFamilyForm(Request $request): RedirectResponse
    {
        $event = $this->activeEvent();

        if (! $event) {
            return back()->withErrors([
                'head_of_family_name' => 'Belum ada acara aktif yang menerima formulir warga.',
            ])->withInput();
        }

        $request->merge([
            'contribution_iuran_amount' => $this->normalizeMoneyInput($request->input('contribution_iuran_amount')),
            'contribution_tambahan_amount' => $this->normalizeMoneyInput($request->input('contribution_tambahan_amount')),
            'contribution_donasi_amount' => $this->normalizeMoneyInput($request->input('contribution_donasi_amount')),
            'contribution_sponsor_amount' => $this->normalizeMoneyInput($request->input('contribution_sponsor_amount')),
        ]);

        $validated = $request->validate([
            'head_of_family_name' => ['required', 'string', 'max:255'],
            'resident_block' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:transfer,cash,other'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'contribution_iuran_amount' => ['nullable', 'numeric', 'min:0'],
            'contribution_iuran_note' => ['nullable', 'string', 'max:500'],
            'contribution_tambahan_amount' => ['nullable', 'numeric', 'min:0'],
            'contribution_tambahan_note' => ['nullable', 'string', 'max:500'],
            'contribution_donasi_amount' => ['nullable', 'numeric', 'min:0'],
            'contribution_donasi_note' => ['nullable', 'string', 'max:500'],
            'contribution_sponsor_amount' => ['nullable', 'numeric', 'min:0'],
            'contribution_sponsor_label' => ['nullable', 'string', 'max:255'],
            'contribution_sponsor_note' => ['nullable', 'string', 'max:500'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.relationship' => ['required', 'in:ayah,ibu,anak,lainnya'],
            'members.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'members.*.gender' => ['nullable', 'in:L,P'],
            'members.*.competition_id' => ['nullable', 'uuid'],
            'members.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $publishedCompetitions = $event->competitions()
            ->where('status', 'published')
            ->pluck('id')
            ->all();

        foreach ($validated['members'] as $index => $member) {
            if (! empty($member['competition_id'])) {
                if (! in_array($member['competition_id'], $publishedCompetitions, true)) {
                    return back()
                        ->withErrors(["members.$index.competition_id" => 'Lomba yang dipilih tidak valid untuk acara ini.'])
                        ->withInput();
                }

                if (($member['relationship'] ?? null) !== 'anak') {
                    return back()
                        ->withErrors(["members.$index.relationship" => 'Pilihan lomba hanya boleh dipilih untuk anggota keluarga dengan hubungan anak.'])
                        ->withInput();
                }
            }
        }

        $contributionDefinitions = [
            'iuran' => [
                'amount' => (float) ($validated['contribution_iuran_amount'] ?? 0),
                'label' => 'Iuran Warga',
                'note' => $validated['contribution_iuran_note'] ?? null,
            ],
            'tambahan' => [
                'amount' => (float) ($validated['contribution_tambahan_amount'] ?? 0),
                'label' => 'Tambahan Sukarela',
                'note' => $validated['contribution_tambahan_note'] ?? null,
            ],
            'donasi' => [
                'amount' => (float) ($validated['contribution_donasi_amount'] ?? 0),
                'label' => 'Donasi',
                'note' => $validated['contribution_donasi_note'] ?? null,
            ],
            'sponsor' => [
                'amount' => (float) ($validated['contribution_sponsor_amount'] ?? 0),
                'label' => $validated['contribution_sponsor_label'] ?? 'Sponsor',
                'note' => $validated['contribution_sponsor_note'] ?? null,
            ],
        ];

        $contributionItems = collect($contributionDefinitions)
            ->filter(fn (array $item) => $item['amount'] > 0)
            ->all();

        if ($contributionItems === []) {
            return back()
                ->withErrors(['contribution_iuran_amount' => 'Isi minimal satu jenis kontribusi agar form bisa dikirim.'])
                ->withInput();
        }

        $proofPath = $request->file('proof_file')?->store('family-submissions/proofs', 'public');

        $referenceCode = 'REG-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));

        DB::transaction(function () use ($event, $validated, $contributionItems, $proofPath, $referenceCode): void {
            $submission = FamilySubmission::create([
                'event_id' => $event->id,
                'reference_code' => $referenceCode,
                'head_of_family_name' => $validated['head_of_family_name'],
                'resident_block' => $validated['resident_block'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recommended_amount' => $event->recommended_contribution_amount,
                'submitted_total' => collect($contributionItems)->sum('amount'),
                'payment_method' => $validated['payment_method'],
                'proof_file' => $proofPath,
                'payment_notes' => $validated['payment_notes'] ?? null,
                'status' => 'submitted',
            ]);

            foreach ($contributionItems as $type => $item) {
                ContributionItem::create([
                    'family_submission_id' => $submission->id,
                    'type' => $type,
                    'amount' => $item['amount'],
                    'label' => $item['label'],
                    'note' => $item['note'],
                ]);
            }

            foreach ($validated['members'] as $member) {
                FamilyMember::create([
                    'family_submission_id' => $submission->id,
                    'name' => $member['name'],
                    'relationship' => $member['relationship'],
                    'age' => $member['age'] ?: null,
                    'gender' => $member['gender'] ?: null,
                    'competition_id' => $member['competition_id'] ?: null,
                    'notes' => $member['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('public.family-form')
            ->with('success_message', 'Form keluarga berhasil dikirim. Silakan tunggu verifikasi panitia.')
            ->with('reference_code', $referenceCode);
    }
}
