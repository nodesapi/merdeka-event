<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'family_submission_id',
    'reference_code',
    'name',
    'resident_block',
    'phone_number',
    'jenis_jualan',
])]
class BazaarSubmission extends Model
{
    use HasFactory, HasUuidV7;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function familySubmission(): BelongsTo
    {
        return $this->belongsTo(FamilySubmission::class);
    }

    /**
     * Cari FamilySubmission (event aktif, bukan yang ditolak) dengan No. HP yang cocok
     * (dinormalisasi) dengan nomor yang diisi warga di form bazaar. Dipakai untuk
     * memastikan pendaftar bazaar sudah terdaftar di Data Warga.
     */
    public static function resolveEligibleFamily(Event $event, string $phone): ?FamilySubmission
    {
        $normalized = FamilySubmission::normalizePhone($phone);

        if (! $normalized) {
            return null;
        }

        return FamilySubmission::where('event_id', $event->id)
            ->where('status', '!=', 'rejected')
            ->get()
            ->first(fn (FamilySubmission $s) => FamilySubmission::normalizePhone($s->phone_number) === $normalized);
    }

    /**
     * Apakah jenis jualan ini sudah didaftarkan warga lain di event yang sama
     * (case-insensitive, spasi diabaikan). $exceptId dipakai saat admin mengedit
     * baris yang sudah ada, supaya baris itu sendiri tidak dianggap bentrok.
     */
    public static function jenisJualanTaken(Event $event, string $jenisJualan, ?string $exceptId = null): bool
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $jenisJualan));

        return static::where('event_id', $event->id)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->whereRaw('LOWER(TRIM(jenis_jualan)) = ?', [strtolower($normalized)])
            ->exists();
    }
}
