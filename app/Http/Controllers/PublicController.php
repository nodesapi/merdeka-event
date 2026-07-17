<?php

namespace App\Http\Controllers;

use App\Models\BazaarSubmission;
use App\Models\ContributionItem;
use App\Models\CommitteeMember;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\FamilySubmission;
use App\Models\RabFundingSource;
use App\Models\RabItem;
use App\Models\Transaction;
use App\Support\AgeCategory;
use App\Support\PayHook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $schedules = $event
            ? $event->eventSchedules()->orderBy('scheduled_at')->orderBy('sort_order')->orderBy('time_label')->get()
            : collect();

        $goodyBagItems = $event
            ? $event->goodyBagItems()->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $bazaarSubmissions = $event
            ? $event->bazaarSubmissions()->latest()->take(6)->get()
            : collect();

        return view('public.home', [
            'event' => $event,
            'committeeCount' => $event ? $event->committeeMembers()->where('is_active', true)->count() : 0,
            'competitions' => $competitions,
            'winners' => $winners,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'schedules' => $schedules,
            'goodyBagItems' => $goodyBagItems,
            'bazaarSubmissions' => $bazaarSubmissions,
            'bazaarSubmissionsCount' => $event ? $event->bazaarSubmissions()->count() : 0,
        ]);
    }

    public function schedule(): View
    {
        $event = $this->activeEvent();

        $schedules = $event
            ? $event->eventSchedules()->orderBy('scheduled_at')->orderBy('sort_order')->orderBy('time_label')->get()
            : collect();

        $scheduleRangeLabel = Event::formatDateList($schedules->pluck('scheduled_at'));

        return view('public.schedule', [
            'event' => $event,
            'schedules' => $schedules,
            'scheduleRangeLabel' => $scheduleRangeLabel,
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
        if ($competition->isGroup()) {
            $competition->load(['event', 'teams' => function ($query) {
                $query->with('members')
                    ->orderByDesc('round')
                    ->orderBy('rank')
                    ->orderBy('created_at');
            }]);

            return view('public.competition-show', [
                'event' => $competition->event,
                'competition' => $competition,
                'teams' => $competition->teams,
                'winnerTeams' => $competition->teams->whereNotNull('rank')->sortBy('rank')->values(),
            ]);
        }

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

        $rabByCategory = RabItem::orderBy('kategori')->orderBy('nama_item')->get()
            ->groupBy('kategori')
            ->map(fn ($items) => [
                'items' => $items,
                'rencana' => (float) $items->sum('jumlah_rencana'),
                'realisasi' => (float) $items->sum('realisasi'),
                'selisih' => (float) $items->sum('jumlah_rencana') - (float) $items->sum('realisasi'),
            ])
            ->sortKeys();

        $totalRabRencana = (float) $rabByCategory->sum('rencana');
        $totalRabRealisasi = (float) $rabByCategory->sum('realisasi');

        $fundingByCategory = RabFundingSource::orderBy('kategori')->orderBy('sumber')->get()
            ->groupBy('kategori')
            ->map(fn ($items) => [
                'items' => $items,
                'target' => (float) $items->sum('target'),
                'realisasi' => (float) $items->sum('realisasi'),
            ])
            ->sortKeys();

        $iuranTarget = (float) ($event?->contribution_target_amount ?? 0);
        $iuranRealisasi = (float) ($event?->iuran_realisasi ?? 0);

        // Target Dana = Total Kebutuhan Anggaran (RAB), bukan hasil jumlah target tiap sumber dana.
        $sisaSetelahIuran = max(0, $totalRabRencana - $iuranTarget);
        $totalRealisasiDana = $iuranRealisasi + (float) $fundingByCategory->sum('realisasi');

        return view('public.finance', [
            'event' => $event,
            'incomeTransactions' => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'rabByCategory' => $rabByCategory,
            'totalRabRencana' => $totalRabRencana,
            'totalRabRealisasi' => $totalRabRealisasi,
            'totalRabSelisih' => $totalRabRencana - $totalRabRealisasi,
            'fundingByCategory' => $fundingByCategory,
            'iuranTarget' => $iuranTarget,
            'iuranRealisasi' => $iuranRealisasi,
            'sisaSetelahIuran' => $sisaSetelahIuran,
            'totalRealisasiDana' => $totalRealisasiDana,
        ]);
    }

    public function familyForm(): View
    {
        $event = $this->activeEvent();

        return view('public.family-form', [
            'event' => $event,
        ]);
    }

    public function bazaarForm(): View
    {
        $event = $this->activeEvent();

        return view('public.bazaar-form', [
            'event' => $event,
            'bazaarSubmissions' => $event
                ? $event->bazaarSubmissions()->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function storeBazaarForm(Request $request): RedirectResponse
    {
        $event = $this->activeEvent();

        if (! $event) {
            return back()->withErrors([
                'name' => 'Belum ada acara aktif yang menerima pendaftaran bazaar.',
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'resident_block' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:50'],
            'jenis_jualan' => ['required', 'string', 'max:255'],
        ]);

        $family = BazaarSubmission::resolveEligibleFamily($event, $validated['phone_number']);

        if (! $family) {
            return back()->withErrors([
                'phone_number' => 'Nomor HP ini belum terdaftar di Data Warga. Silakan isi Form Warga terlebih dahulu sebelum daftar bazaar.',
            ])->withInput();
        }

        $existing = BazaarSubmission::where('family_submission_id', $family->id)->first();

        if ($existing) {
            return back()->withErrors([
                'phone_number' => 'Keluarga ini sudah terdaftar bazaar dengan jenis jualan "' . $existing->jenis_jualan . '". Satu keluarga hanya boleh 1 lapak.',
            ])->withInput();
        }

        $jenisJualan = trim(preg_replace('/\s+/', ' ', $validated['jenis_jualan']));

        if (BazaarSubmission::jenisJualanTaken($event, $jenisJualan)) {
            return back()->withErrors([
                'jenis_jualan' => 'Jenis jualan "' . $jenisJualan . '" sudah didaftarkan warga lain. Silakan pilih jenis jualan lain.',
            ])->withInput();
        }

        $referenceCode = 'BZR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));

        BazaarSubmission::create([
            'event_id' => $event->id,
            'family_submission_id' => $family->id,
            'reference_code' => $referenceCode,
            'name' => $validated['name'],
            'resident_block' => $validated['resident_block'],
            'phone_number' => $validated['phone_number'],
            'jenis_jualan' => $jenisJualan,
        ]);

        return redirect()->route('public.bazaar-form')
            ->with('success_message', 'Pendaftaran bazaar berhasil dan langsung dikonfirmasi, tidak perlu menunggu verifikasi panitia!')
            ->with('reference_code', $referenceCode);
    }

    public function terms(): View
    {
        return view('public.terms', [
            'event' => $this->activeEvent(),
        ]);
    }

    public function galeri(): View
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        $photos = collect(Storage::disk('public')->files('galeri'))
            ->filter(fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true))
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values()
            ->map(function (string $path) {
                $filename = basename($path);

                return [
                    // Root-relative & URL-encoded so it resolves on any host/port and handles filenames with spaces/parentheses.
                    'full' => '/storage/' . implode('/', array_map('rawurlencode', explode('/', $path))),
                    'thumb' => route('public.galeri.thumb', ['filename' => $filename]),
                ];
            });

        return view('public.galeri', [
            'event' => $this->activeEvent(),
            'photos' => $photos,
        ]);
    }

    /**
     * Serve a resized/compressed copy of a galeri photo, generating it on first request.
     * Originals can be several MB each (raw WhatsApp exports), which made the grid load sluggishly.
     */
    public function galeriThumbnail(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $sourceRelative = 'galeri/'.$filename;

        abort_unless(Storage::disk('public')->exists($sourceRelative), 404);

        $thumbRelative = 'galeri-thumbs/'.$filename;
        $thumbPath = Storage::disk('public')->path($thumbRelative);

        if (! Storage::disk('public')->exists($thumbRelative)) {
            Storage::disk('public')->makeDirectory('galeri-thumbs');
            $this->makeGaleriThumbnail(Storage::disk('public')->path($sourceRelative), $thumbPath);
        }

        return response()->file($thumbPath, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }

    protected function makeGaleriThumbnail(string $sourcePath, string $destPath, int $maxDimension = 640): void
    {
        $info = @getimagesize($sourcePath);

        $image = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (! $image) {
            copy($sourcePath, $destPath);

            return;
        }

        if (function_exists('exif_read_data') && ($info[2] ?? null) === IMAGETYPE_JPEG) {
            $orientation = (@exif_read_data($sourcePath) ?: [])['Orientation'] ?? null;
            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, $maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($thumb, $destPath, 72);

        imagedestroy($image);
        imagedestroy($thumb);
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

        $minIuran = (float) ($event->recommended_contribution_amount ?? 0);

        $validated = $request->validate([
            'head_of_family_name' => ['required', 'string', 'max:255'],
            'head_of_family_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'head_of_family_gender' => ['nullable', 'in:L,P'],
            'resident_block' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:transfer,cash,other,qris'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'contribution_iuran_amount' => $minIuran > 0
                ? ['required', 'numeric', 'min:' . $minIuran]
                : ['nullable', 'numeric', 'min:0'],
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
            'contribution_iuran_amount.required' => 'Nominal iuran wajib diisi, minimal Rp' . number_format($minIuran, 0, ',', '.') . '.',
            'contribution_iuran_amount.min' => 'Nominal iuran tidak boleh kurang dari Rp' . number_format($minIuran, 0, ',', '.') . ' sesuai ketentuan panitia.',
        ]);

        // Cegah pendaftaran ganda: satu No. HP (dinormalisasi 0<->62) hanya boleh punya
        // satu pendaftaran aktif per acara. Untuk menambah anggota, lewat panitia
        // (tanpa iuran ulang). Pendaftaran yang sudah "rejected" tidak menghalangi.
        $normalizedPhone = FamilySubmission::normalizePhone($validated['phone_number']);
        if ($normalizedPhone) {
            $existing = FamilySubmission::where('event_id', $event->id)
                ->where('status', '!=', 'rejected')
                ->get()
                ->first(fn (FamilySubmission $s) => FamilySubmission::normalizePhone($s->phone_number) === $normalizedPhone);

            if ($existing) {
                $message = 'Nomor HP ini sudah terdaftar (No. Ref ' . $existing->reference_code . ' a/n ' . $existing->head_of_family_name . '). ';

                if ($existing->payment_method === 'qris' && $existing->payment_status !== 'paid') {
                    $message .= 'Pendaftaran Anda masih menunggu pembayaran — silakan lanjutkan pembayaran QRIS atau hubungi panitia.';
                } else {
                    $message .= 'Jika ingin menambah anggota keluarga, hubungi panitia — tidak perlu iuran lagi.';
                }

                return back()->withErrors(['phone_number' => $message])->withInput();
            }
        }

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

        $result = DB::transaction(function () use ($event, $validated, $members, $contributionItems, $proofPath, $referenceCode): array {
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

            return ['submission' => $submission, 'members' => $created];
        });

        /** @var FamilySubmission $submission */
        $submission = $result['submission'];
        $registeredMembers = $result['members'];

        // Jika warga memilih QRIS & PayHook aktif: buat invoice QRIS dinamis lalu
        // arahkan ke halaman pembayaran. Bila gagal, jatuh ke alur manual biasa.
        if ($validated['payment_method'] === 'qris') {
            $payhook = new PayHook();

            if ($payhook->enabled()) {
                $invoice = $payhook->createQrisInvoice(
                    amount: (float) $submission->submitted_total,
                    customerName: $submission->head_of_family_name,
                    externalId: $submission->reference_code,
                    phone: $submission->phone_number,
                    description: 'Iuran warga ' . $submission->reference_code,
                    expiresInMinutes: 60,
                );

                if ($invoice) {
                    $submission->update([
                        'payment_provider' => 'payhook',
                        'payment_invoice_number' => $invoice['invoice_number'],
                        'payment_pay_amount' => $invoice['pay_amount'],
                        'payment_qris_svg' => $invoice['qris_svg'],
                        'payment_status' => 'pending',
                        'payment_expires_at' => $invoice['expires_at'] ? \Illuminate\Support\Carbon::parse($invoice['expires_at']) : null,
                    ]);

                    return redirect()
                        ->route('public.qris-payment', $submission->reference_code)
                        ->with('reference_code', $referenceCode)
                        ->with('registered_members', $registeredMembers);
                }

                return redirect()
                    ->route('public.family-form')
                    ->with('warning_message', 'Form terkirim, tetapi QR pembayaran gagal dibuat. Silakan hubungi panitia atau gunakan metode transfer manual.')
                    ->with('reference_code', $referenceCode)
                    ->with('registered_members', $registeredMembers);
            }
        }

        return redirect()
            ->route('public.family-form')
            ->with('success_message', 'Form keluarga berhasil dikirim. Simpan No Daftar tiap anggota di bawah ini.')
            ->with('reference_code', $referenceCode)
            ->with('registered_members', $registeredMembers);
    }

    /**
     * Halaman pembayaran QRIS untuk sebuah submission (setelah Form Warga dikirim).
     */
    public function qrisPayment(FamilySubmission $submission): View
    {
        // Halaman ini khusus metode QRIS.
        abort_unless($submission->payment_method === 'qris', 404);

        $payhook = new PayHook();

        // "Lanjutkan pembayaran": kalau belum lunas dan invoice belum ada / QR kedaluwarsa,
        // buat invoice QRIS baru supaya warga yang balik lagi tetap dapat QR yang valid.
        if ($submission->payment_status !== 'paid' && $payhook->enabled()) {
            $expired = $submission->payment_expires_at && $submission->payment_expires_at->isPast();
            $missing = ! filled($submission->payment_invoice_number) || ! filled($submission->payment_qris_svg);

            if ($expired || $missing) {
                $invoice = $payhook->createQrisInvoice(
                    amount: (float) $submission->submitted_total,
                    customerName: $submission->head_of_family_name,
                    // external_id unik agar PayHook tidak me-replay invoice lama (idempotency).
                    externalId: $submission->reference_code . '-' . now()->format('YmdHis'),
                    phone: $submission->phone_number,
                    description: 'Iuran warga ' . $submission->reference_code,
                    expiresInMinutes: 60,
                );

                if ($invoice) {
                    $submission->update([
                        'payment_provider' => 'payhook',
                        'payment_invoice_number' => $invoice['invoice_number'],
                        'payment_pay_amount' => $invoice['pay_amount'],
                        'payment_qris_svg' => $invoice['qris_svg'],
                        'payment_status' => 'pending',
                        'payment_expires_at' => $invoice['expires_at'] ? \Illuminate\Support\Carbon::parse($invoice['expires_at']) : null,
                    ]);
                }
            }
        }

        return view('public.qris-payment', [
            'event' => $this->activeEvent(),
            'submission' => $submission,
            'registeredMembers' => session('registered_members', []),
        ]);
    }

    /**
     * Endpoint status pembayaran (dipolling halaman QRIS).
     */
    public function qrisStatus(FamilySubmission $submission): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => $submission->payment_status,
            'paid' => $submission->payment_status === 'paid',
            'paid_at' => optional($submission->payment_paid_at)->toIso8601String(),
        ]);
    }

    /**
     * Bukti pendaftaran (siap cetak / simpan PDF) untuk satu keluarga.
     */
    public function registrationReceipt(FamilySubmission $submission): View
    {
        $submission->load(['event', 'familyMembers' => fn ($q) => $q->orderBy('registration_number'), 'contributionItems']);

        return view('public.registration-receipt', [
            'submission' => $submission,
            'site' => \App\Models\SiteSetting::current(),
            'generatedAt' => now(),
        ]);
    }

    /**
     * Halaman form pendaftaran lomba (terpisah dari Form Warga).
     */
    public function lombaForm(): View
    {
        $event = $this->activeEvent();

        // Lomba grup diatur & diisi panitia lewat panel admin, tidak lewat form publik ini.
        $competitions = $event
            ? $event->competitions()->where('status', 'published')->where('type', 'individual')->orderBy('name')->get()
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

        if (! $event->isLombaRegistrationOpen()) {
            return response()->json(['found' => false, 'message' => 'Pendaftaran lomba belum dibuka.'], 403);
        }

        $number = trim((string) $request->query('no', ''));

        if ($number === '') {
            return response()->json(['found' => false, 'message' => 'Masukkan No Daftar.'], 422);
        }

        $member = FamilyMember::where('event_id', $event->id)
            ->where('registration_number', $number)
            ->whereHas('familySubmission', fn ($q) => $q->where('status', '!=', 'rejected'))
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
            ->where('type', 'individual')
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

        if (! $event->isLombaRegistrationOpen()) {
            return back()->withErrors(['registration_number' => 'Pendaftaran lomba belum dibuka.'])->withInput();
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
            ->where('type', 'individual')
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
