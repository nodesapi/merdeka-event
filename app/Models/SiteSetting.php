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
    'contact_whatsapp',
    'contact_person',
    'bank_name',
    'bank_account_number',
    'bank_account_holder',
])]
class SiteSetting extends Model
{
    use HasUuidV7;

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
