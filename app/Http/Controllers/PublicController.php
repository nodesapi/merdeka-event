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
use App\Support\AgeCategory;
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

        $participantsByCategory = $competition->participants
            ->groupBy(fn ($p) => $p->age_category_key ?? 'none')
            ->sortBy(fn ($group, $key) => AgeCategory::order($key === 'none' ? null : $key));

        $winnersByCategory = $competition->participants
            ->whereNotNull('rank')
            ->groupBy(fn ($p) => $p->age_category_key ?? 'none')
            ->sortBy(fn ($group, $key) => AgeCategory::order($key === 'none' ? null : $key))
            ->map(fn ($group) => $group->sortBy('rank')->values());

        return view('public.competition-show', [
            'event' => $competition->event,
            'competition' => $competition,
            'participantsByCategory' => $participantsByCategory,
            'winnersByCategory' => $winnersByCategory,
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

        return view('public.family-form', [
            'event' => $event,
        ]);
    }

    public function terms(): View
    {
        return view('public.terms', [
            'event' => $this->activeEvent(),
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
            'head_of_family_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'head_of_family_gender' => ['nullable', 'in:L,P'],
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
            'terms' => ['accepted'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.name' => ['nullable', 'string', 'max:255'],
            'members.*.relationship' => ['required', 'in:ayah,ibu,anak,lainnya'],
            'members.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'members.*.gender' => ['nullable', 'in:L,P'],
            'members.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan terlebih dahulu.',
        ]);

        // Baris anggota lain yang namanya kosong diabaikan (form menyediakan beberapa slot).
        $members = collect($validated['members'])
            ->filter(fn ($member) => filled($member['name'] ?? null))
            ->values();

        // Kepala keluarga otomatis jadi anggota #1 dan ikut dapat No Daftar (lomba & doorprize).
        $members = $members->prepend([
            'name' => $validated['head_of_family_name'],
            'relationship' => ($validated['head_of_family_gender'] ?? null) === 'P' ? 'ibu' : 'ayah',
            'age' => $validated['head_of_family_age'] ?? null,
            'gender' => $validated['head_of_family_gender'] ?? null,
            'notes' => null,
        ])->values();

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

        $registeredMembers = DB::transaction(function () use ($event, $validated, $members, $contributionItems, $proofPath, $referenceCode): array {
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

            // No Daftar berurutan per acara (dipakai untuk lomba, doorprize, registrasi ulang).
            $sequence = (int) FamilyMember::where('event_id', $event->id)
                ->max(DB::raw('CAST(registration_number AS INTEGER)'));

            $created = [];

            foreach ($members as $member) {
                $sequence++;
                $registrationNumber = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                FamilyMember::create([
                    'family_submission_id' => $submission->id,
                    'event_id' => $event->id,
                    'registration_number' => $registrationNumber,
                    'name' => $member['name'],
                    'relationship' => $member['relationship'],
                    'age' => ($member['age'] ?? null) !== null && $member['age'] !== '' ? (int) $member['age'] : null,
                    'gender' => $member['gender'] ?: null,
                    'notes' => $member['notes'] ?? null,
                ]);

                $created[] = [
                    'registration_number' => $registrationNumber,
                    'name' => $member['name'],
                    'relationship' => $member['relationship'],
                ];
            }

            return $created;
        });

        return redirect()
            ->route('public.family-form')
            ->with('success_message', 'Form keluarga berhasil dikirim. Simpan No Daftar tiap anggota di bawah ini.')
            ->with('reference_code', $referenceCode)
            ->with('registered_members', $registeredMembers);
    }

    /**
     * Halaman form pendaftaran lomba (terpisah dari Form Warga).
     */
    public function lombaForm(): View
    {
        $event = $this->activeEvent();

        $competitions = $event
            ? $event->competitions()->where('status', 'published')->orderBy('name')->get()
            : collect();

        return view('public.lomba-register', [
            'event' => $event,
            'competitions' => $competitions,
        ]);
    }

    /**
     * Lookup anggota berdasarkan No Daftar (dipanggil via fetch JSON).
     */
    public function lombaLookup(Request $request)
    {
        $event = $this->activeEvent();

        if (! $event) {
            return response()->json(['found' => false, 'message' => 'Belum ada acara aktif.'], 404);
        }

        $number = trim((string) $request->query('no', ''));

        if ($number === '') {
            return response()->json(['found' => false, 'message' => 'Masukkan No Daftar.'], 422);
        }

        $member = FamilyMember::where('event_id', $event->id)
            ->where('registration_number', $number)
            ->first();

        if (! $member) {
            return response()->json([
                'found' => false,
                'message' => 'No Daftar tidak ditemukan. Pastikan sudah mengisi Form Warga dan angkanya benar.',
            ], 404);
        }

        $registeredIds = $member->competitionParticipations()->pluck('competition_id')->all();

        $competitions = $event->competitions()
            ->where('status', 'published')
            ->orderBy('name')
            ->get()
            ->map(function (Competition $competition) use ($member, $registeredIds) {
                $eligible = $competition->isAgeEligible($member->age !== null ? (int) $member->age : null);
                $already = in_array($competition->id, $registeredIds, true);

                return [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'age_limit' => $competition->age_limit_label,
                    'eligible' => $eligible,
                    'already' => $already,
                    'reason' => $already
                        ? 'Sudah terdaftar'
                        : (! $eligible ? 'Tidak sesuai umur' : null),
                ];
            })
            ->values();

        return response()->json([
            'found' => true,
            'member' => [
                'registration_number' => $member->registration_number,
                'name' => $member->name,
                'age' => $member->age,
                'gender_label' => $member->gender_label,
                'category' => $member->age_category_label,
            ],
            'competitions' => $competitions,
        ]);
    }

    /**
     * Simpan pendaftaran lomba (satu No Daftar bisa memilih beberapa lomba).
     */
    public function storeLombaForm(Request $request): RedirectResponse
    {
        $event = $this->activeEvent();

        if (! $event) {
            return back()->withErrors(['registration_number' => 'Belum ada acara aktif.'])->withInput();
        }

        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:20'],
            'competition_ids' => ['required', 'array', 'min:1'],
            'competition_ids.*' => ['uuid'],
        ], [
            'competition_ids.required' => 'Pilih minimal satu lomba yang ingin diikuti.',
        ]);

        $member = FamilyMember::where('event_id', $event->id)
            ->where('registration_number', $validated['registration_number'])
            ->first();

        if (! $member) {
            return back()
                ->withErrors(['registration_number' => 'No Daftar tidak ditemukan untuk acara ini.'])
                ->withInput();
        }

        $age = $member->age !== null ? (int) $member->age : null;

        $competitions = $event->competitions()
            ->where('status', 'published')
            ->whereIn('id', $validated['competition_ids'])
            ->get();

        $alreadyIds = $member->competitionParticipations()->pluck('competition_id')->all();

        $registeredNames = [];
        $skipped = [];

        DB::transaction(function () use ($competitions, $member, $age, $alreadyIds, &$registeredNames, &$skipped): void {
            foreach ($competitions as $competition) {
                if (in_array($competition->id, $alreadyIds, true)) {
                    $skipped[] = $competition->name . ' (sudah terdaftar)';
                    continue;
                }

                if (! $competition->isAgeEligible($age)) {
                    $skipped[] = $competition->name . ' (tidak sesuai umur)';
                    continue;
                }

                CompetitionParticipant::create([
                    'competition_id' => $competition->id,
                    'family_member_id' => $member->id,
                    'name' => $member->name,
                    'resident_block' => optional($member->familySubmission)->resident_block,
                    'phone_number' => optional($member->familySubmission)->phone_number,
                    'age' => $age,
                    'round' => 1,
                    'status' => 'active',
                ]);

                $registeredNames[] = $competition->name;
            }
        });

        if ($registeredNames === []) {
            return back()
                ->withErrors(['competition_ids' => 'Tidak ada lomba baru yang bisa didaftarkan: ' . implode(', ', $skipped)])
                ->withInput();
        }

        $message = $member->name . ' berhasil didaftarkan ke: ' . implode(', ', $registeredNames) . '.';
        if ($skipped !== []) {
            $message .= ' Dilewati: ' . implode(', ', $skipped) . '.';
        }

        return redirect()
            ->route('public.lomba-register')
            ->with('success_message', $message);
    }
}
