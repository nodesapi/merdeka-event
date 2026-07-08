<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'event_id',
    'reference_code',
    'head_of_family_name',
    'resident_block',
    'phone_number',
    'email',
    'notes',
    'recommended_amount',
    'submitted_total',
    'payment_method',
    'proof_file',
    'payment_notes',
    'status',
    'admin_notes',
    'verified_at',
    'payment_provider',
    'payment_invoice_number',
    'payment_pay_amount',
    'payment_qris_svg',
    'payment_status',
    'payment_expires_at',
    'payment_paid_at',
])]
class FamilySubmission extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'recommended_amount' => 'decimal:2',
            'submitted_total' => 'decimal:2',
            'verified_at' => 'datetime',
            'payment_pay_amount' => 'decimal:2',
            'payment_expires_at' => 'datetime',
            'payment_paid_at' => 'datetime',
        ];
    }

    /**
     * Label metode pembayaran untuk pencatatan transaksi.
     */
    public static function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'transfer' => 'Transfer',
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            default => 'Lainnya',
        };
    }

    /**
     * Verifikasi submission: catat tiap ContributionItem sebagai Transaction income,
     * daftarkan anggota ke lomba, dan tandai submission "verified".
     *
     * Idempotent (firstOrCreate) sehingga aman dipanggil dari verifikasi panitia
     * maupun webhook pembayaran tanpa dobel-catat.
     */
    public function approveAndRecord(?string $notes = null): void
    {
        DB::transaction(function () use ($notes): void {
            $this->loadMissing(['contributionItems', 'familyMembers.competition']);

            foreach ($this->contributionItems as $item) {
                Transaction::firstOrCreate(
                    ['contribution_item_id' => $item->id],
                    [
                        'user_id' => null,
                        'amount' => $item->amount,
                        'type' => 'income',
                        'bank_name' => static::paymentMethodLabel($this->payment_method),
                        'account_number' => $this->reference_code,
                        'resident_block' => $this->resident_block,
                        'description' => trim(($item->label ?: ucfirst($item->type)) . ' - ' . $this->head_of_family_name . ($item->note ? ' (' . $item->note . ')' : '')),
                        'status' => 'approved',
                    ]
                );
            }

            foreach ($this->familyMembers->whereNotNull('competition_id') as $member) {
                CompetitionParticipant::firstOrCreate(
                    ['family_member_id' => $member->id],
                    [
                        'competition_id' => $member->competition_id,
                        'name' => $member->name,
                        'resident_block' => $this->resident_block,
                        'phone_number' => $this->phone_number,
                        'round' => 1,
                        'status' => 'active',
                        'rank' => null,
                        'notes' => 'Pendaftaran via form warga ' . $this->reference_code,
                    ]
                );
            }

            $this->update([
                'status' => 'verified',
                'admin_notes' => $notes ?? $this->admin_notes,
                'verified_at' => now(),
            ]);
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contributionItems(): HasMany
    {
        return $this->hasMany(ContributionItem::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function getProofFileUrlAttribute(): ?string
    {
        if (! $this->proof_file) {
            return null;
        }

        return '/storage/' . ltrim($this->proof_file, '/');
    }

    /**
     * Normalisasi nomor HP ke format 62xxxx (buang non-digit, samakan 0/62).
     * Dipakai untuk WhatsApp & deteksi pendaftaran ganda.
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return '62' . $digits;
    }

    /**
     * Nomor WhatsApp ternormalisasi (62...), atau null bila kosong.
     */
    public function whatsappDigits(): ?string
    {
        return static::normalizePhone($this->phone_number);
    }

    /**
     * Pesan siap-kirim (WhatsApp) berisi No. Daftar tiap anggota + info pembayaran.
     */
    public function registrationMessage(): string
    {
        $this->loadMissing([
            'event',
            'familyMembers' => fn ($q) => $q->orderBy('registration_number'),
        ]);

        $lines = [];
        $lines[] = '*BUKTI PENDAFTARAN WARGA*';
        if ($this->event?->name) {
            $lines[] = $this->event->name;
        }
        $lines[] = '';
        $lines[] = 'Halo ' . $this->head_of_family_name . ', berikut data pendaftaran keluarga Anda:';
        $lines[] = '';
        $lines[] = 'No. Registrasi: ' . $this->reference_code;
        $lines[] = 'Blok: ' . ($this->resident_block ?: '-');
        $lines[] = '';
        $lines[] = '*No. Daftar Anggota:*';
        foreach ($this->familyMembers as $member) {
            $lines[] = '- ' . $member->name . ' (' . ucfirst($member->relationship) . '): *' . ($member->registration_number ?: '-') . '*';
        }
        $lines[] = '';
        $lines[] = 'Iuran/Kontribusi: Rp ' . number_format((float) $this->submitted_total, 0, ',', '.');

        if ($this->payment_status === 'paid') {
            $lines[] = 'Status Pembayaran: *LUNAS*';
            if ($this->payment_paid_at) {
                $lines[] = 'Dibayar: ' . $this->payment_paid_at->timezone(config('app.timezone'))->format('d M Y H:i');
            }
        } elseif ($this->status === 'verified') {
            $lines[] = 'Status: *TERVERIFIKASI*';
        } elseif ($this->payment_method === 'qris') {
            // Belum lunas & metode QRIS: sertakan link lanjutkan pembayaran.
            $lines[] = 'Status Pembayaran: *BELUM DIBAYAR*';
            $lines[] = '';
            $lines[] = 'Lanjutkan pembayaran QRIS di:';
            $lines[] = route('public.qris-payment', $this->reference_code);
        }

        $lines[] = '';
        $lines[] = 'Lihat / simpan bukti & data (PDF):';
        $lines[] = route('public.registration-receipt', $this->reference_code);
        $lines[] = '';
        $lines[] = 'Simpan No. Daftar ini untuk pendaftaran lomba & pengambilan doorprize. Terima kasih.';

        return implode("\n", $lines);
    }

    /**
     * Link click-to-chat WhatsApp (buka WA Web/app dengan pesan terisi).
     */
    public function whatsappUrl(): ?string
    {
        $digits = $this->whatsappDigits();

        if (! $digits) {
            return null;
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($this->registrationMessage());
    }
}
