<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'site_name',
    'tagline',
    'logo_path',
    'favicon_path',
    'hero_banner_path',
    'og_image_path',
    'google_site_verification',
    'contact_whatsapp',
    'contact_person',
    'bank_name',
    'bank_account_number',
    'bank_account_holder',
    'terms_conditions',
    'welcome_enabled',
    'welcome_title',
    'welcome_message',
])]
class SiteSetting extends Model
{
    use HasUuidV7;

    protected function casts(): array
    {
        return [
            'welcome_enabled' => 'boolean',
        ];
    }

    public function getWelcomeTitleTextAttribute(): string
    {
        return trim((string) $this->welcome_title) !== ''
            ? $this->welcome_title
            : 'Selamat Datang';
    }

    public function getWelcomeMessageTextAttribute(): string
    {
        return trim((string) $this->welcome_message) !== ''
            ? $this->welcome_message
            : 'Selamat datang di portal warga. Klik tombol di bawah untuk masuk dan nikmati suasana kemerdekaan dengan iringan lagu perjuangan.';
    }

    /** Isi Syarat & Ketentuan default (dummy) bila admin belum mengisi. */
    public const DEFAULT_TERMS = <<<'TXT'
1. Data yang diisi pada form ini digunakan panitia hanya untuk keperluan pendataan warga dan kegiatan kemerdekaan.
2. Kontribusi (iuran, tambahan sukarela, donasi, maupun sponsor) bersifat sukarela dan tidak mengikat.
3. Seluruh dana yang masuk akan dikelola secara transparan oleh panitia dan dilaporkan melalui halaman transparansi dana.
4. Bukti pembayaran yang diunggah wajib benar dan dapat dipertanggungjawabkan.
5. Dengan mengirimkan form ini, warga menyatakan data yang diisi adalah benar dan menyetujui pengelolaan data oleh panitia.

*Syarat & ketentuan ini masih contoh (dummy) dan dapat diubah panitia melalui menu Pengaturan Website.
TXT;

    public function getTermsConditionsTextAttribute(): string
    {
        return trim((string) $this->terms_conditions) !== ''
            ? $this->terms_conditions
            : self::DEFAULT_TERMS;
    }

    protected static ?self $cached = null;

    /**
     * Return the single settings row (memoised per request),
     * creating an empty one if none exists yet.
     */
    public static function current(): self
    {
        return static::$cached ??= (static::query()->first() ?? static::create([]));
    }

    protected function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Root-relative URL so images resolve on any host/port (dev & prod).
        return '/storage/' . ltrim($path, '/');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->assetUrl($this->logo_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->assetUrl($this->favicon_path);
    }

    public function getHeroBannerUrlAttribute(): ?string
    {
        return $this->assetUrl($this->hero_banner_path);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->assetUrl($this->og_image_path);
    }

    /**
     * Normalised WhatsApp link (https://wa.me/62...).
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->contact_whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->contact_whatsapp);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }
}
