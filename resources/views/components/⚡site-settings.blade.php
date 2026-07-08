<?php

use App\Models\MusicTrack;
use App\Models\SiteSetting;
use App\Support\ImageConverter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $settingId = '';

    public $site_name = '';
    public $tagline = '';
    public $contact_whatsapp = '';
    public $contact_person = '';
    public $bank_name = '';
    public $bank_account_number = '';
    public $bank_account_holder = '';
    public $google_site_verification = '';
    public $terms_conditions = '';

    public bool $welcome_enabled = true;
    public $welcome_title = '';
    public $welcome_message = '';
    public $music_files = [];

    public $logo;
    public $favicon;
    public $hero_banner;
    public $og_image;
    public $bank_logo;
    public $qris_logo;

    public ?string $logo_path = null;
    public ?string $favicon_path = null;
    public ?string $hero_banner_path = null;
    public ?string $og_image_path = null;
    public ?string $bank_logo_path = null;
    public ?string $qris_logo_path = null;

    public bool $payhook_enabled = false;
    public $payhook_base_url = '';
    public $payhook_channel_type = 'qris';
    // Write-only: dibiarkan kosong saat load; hanya diperbarui bila admin mengisi.
    public $payhook_api_key = '';
    public $payhook_webhook_secret = '';

    public $success_message = '';

    public function mount()
    {
        $setting = SiteSetting::current();

        $this->settingId = $setting->id;
        $this->site_name = $setting->site_name;
        $this->tagline = $setting->tagline;
        $this->contact_whatsapp = $setting->contact_whatsapp;
        $this->contact_person = $setting->contact_person;
        $this->bank_name = $setting->bank_name;
        $this->bank_account_number = $setting->bank_account_number;
        $this->bank_account_holder = $setting->bank_account_holder;
        $this->google_site_verification = $setting->google_site_verification ?? '';
        $this->terms_conditions = $setting->terms_conditions ?? '';
        $this->welcome_enabled = (bool) ($setting->welcome_enabled ?? true);
        $this->welcome_title = $setting->welcome_title ?? '';
        $this->welcome_message = $setting->welcome_message ?? '';
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
        $this->og_image_path = $setting->og_image_path;
        $this->bank_logo_path = $setting->bank_logo_path;
        $this->qris_logo_path = $setting->qris_logo_path;
        $this->payhook_enabled = (bool) ($setting->payhook_enabled ?? false);
        $this->payhook_base_url = $setting->payhook_base_url ?? '';
        $this->payhook_channel_type = $setting->payhook_channel_type ?: 'qris';
        // Sengaja TIDAK memuat api_key/webhook_secret ke browser (write-only).
    }

    public function save()
    {
        $this->validate([
            'site_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'contact_whatsapp' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_holder' => 'nullable|string|max:255',
            'google_site_verification' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:20000',
            'welcome_enabled' => 'boolean',
            'welcome_title' => 'nullable|string|max:120',
            'welcome_message' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|max:4096',
            'favicon' => 'nullable|max:1024|mimes:png,ico,svg,jpg,jpeg',
            'hero_banner' => 'nullable|image|max:8192',
            'og_image' => 'nullable|image|max:4096|mimes:jpg,jpeg,png',
            'bank_logo' => 'nullable|image|max:2048',
            'qris_logo' => 'nullable|image|max:2048',
            'payhook_enabled' => 'boolean',
            'payhook_base_url' => 'nullable|url|max:255',
            'payhook_channel_type' => 'nullable|string|max:50',
            'payhook_api_key' => 'nullable|string|max:255',
            'payhook_webhook_secret' => 'nullable|string|max:255',
        ]);

        $setting = SiteSetting::findOrFail($this->settingId);

        $data = [
            'site_name' => $this->site_name,
            'tagline' => $this->tagline,
            'contact_whatsapp' => $this->contact_whatsapp,
            'contact_person' => $this->contact_person,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_holder' => $this->bank_account_holder,
            'google_site_verification' => trim((string) $this->google_site_verification) ?: null,
            'terms_conditions' => trim((string) $this->terms_conditions) ?: null,
            'welcome_enabled' => $this->welcome_enabled,
            'welcome_title' => trim((string) $this->welcome_title) ?: null,
            'welcome_message' => trim((string) $this->welcome_message) ?: null,
            'payhook_enabled' => $this->payhook_enabled,
            'payhook_base_url' => trim((string) $this->payhook_base_url) ?: null,
            'payhook_channel_type' => trim((string) $this->payhook_channel_type) ?: 'qris',
        ];

        // Kredensial hanya diperbarui bila admin mengetiknya (write-only, terenkripsi).
        if (filled($this->payhook_api_key)) {
            $data['payhook_api_key'] = trim($this->payhook_api_key);
        }
        if (filled($this->payhook_webhook_secret)) {
            $data['payhook_webhook_secret'] = trim($this->payhook_webhook_secret);
        }

        if ($this->logo) {
            ImageConverter::delete($setting->logo_path);
            $data['logo_path'] = ImageConverter::storeAsWebp($this->logo, 'site', 512);
        }

        if ($this->favicon) {
            ImageConverter::delete($setting->favicon_path);
            $data['favicon_path'] = ImageConverter::storeOriginal($this->favicon, 'site');
        }

        if ($this->hero_banner) {
            ImageConverter::delete($setting->hero_banner_path);
            $data['hero_banner_path'] = ImageConverter::storeAsWebp($this->hero_banner, 'site', 1920);
        }

        if ($this->og_image) {
            ImageConverter::delete($setting->og_image_path);
            $data['og_image_path'] = ImageConverter::storeOriginal($this->og_image, 'site');
        }

        if ($this->bank_logo) {
            ImageConverter::delete($setting->bank_logo_path);
            $data['bank_logo_path'] = ImageConverter::storeAsWebp($this->bank_logo, 'site', 256);
        }

        if ($this->qris_logo) {
            ImageConverter::delete($setting->qris_logo_path);
            $data['qris_logo_path'] = ImageConverter::storeAsWebp($this->qris_logo, 'site', 256);
        }

        $setting->update($data);

        $this->reset(['logo', 'favicon', 'hero_banner', 'og_image', 'bank_logo', 'qris_logo', 'payhook_api_key', 'payhook_webhook_secret']);
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
        $this->og_image_path = $setting->og_image_path;
        $this->bank_logo_path = $setting->bank_logo_path;
        $this->qris_logo_path = $setting->qris_logo_path;
        $this->success_message = 'Pengaturan website berhasil disimpan.';
    }

    public function removeImage(string $field)
    {
        $setting = SiteSetting::findOrFail($this->settingId);
        $column = $field . '_path';

        ImageConverter::delete($setting->{$column});
        $setting->update([$column => null]);
        $this->{$column} = null;
        $this->success_message = 'Gambar dihapus.';
    }

    public function uploadMusic(): void
    {
        $this->validate([
            'music_files' => 'required|array|max:20',
            'music_files.*' => 'file|mimes:mp3,ogg,wav,m4a,aac|max:12288',
        ], [
            'music_files.*.mimes' => 'Format musik harus mp3, ogg, wav, m4a, atau aac.',
            'music_files.*.max' => 'Ukuran tiap file musik maksimal 12MB.',
        ]);

        $order = (int) MusicTrack::max('sort_order');

        foreach ($this->music_files as $file) {
            $title = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 120, '');
            MusicTrack::create([
                'title' => $title ?: 'Lagu',
                'path' => $file->store('music', 'public'),
                'sort_order' => ++$order,
            ]);
        }

        $this->reset('music_files');
        $this->success_message = 'Musik berhasil diunggah.';
    }

    public function deleteMusic(string $id): void
    {
        $track = MusicTrack::find($id);
        if ($track) {
            Storage::disk('public')->delete($track->path);
            $track->delete();
        }
        $this->success_message = 'Musik dihapus.';
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $setting = SiteSetting::current();

        return [
            'logoUrl' => $this->logo_path ? '/storage/' . ltrim($this->logo_path, '/') : null,
            'faviconUrl' => $this->favicon_path ? '/storage/' . ltrim($this->favicon_path, '/') : null,
            'bannerUrl' => $this->hero_banner_path ? '/storage/' . ltrim($this->hero_banner_path, '/') : null,
            'ogImageUrl' => $this->og_image_path ? '/storage/' . ltrim($this->og_image_path, '/') : null,
            'bankLogoUrl' => $this->bank_logo_path ? '/storage/' . ltrim($this->bank_logo_path, '/') : null,
            'qrisLogoUrl' => $this->qris_logo_path ? '/storage/' . ltrim($this->qris_logo_path, '/') : null,
            'tracks' => MusicTrack::orderBy('sort_order')->get(),
            'payhookApiKeyMasked' => $this->maskSecret($setting->payhook_api_key),
            'payhookSecretMasked' => $this->maskSecret($setting->payhook_webhook_secret),
            'payhookCallbackUrl' => url('/webhook/payhook'),
        ];
    }

    protected function maskSecret(?string $secret): ?string
    {
        if (! $secret) {
            return null;
        }

        $visible = min(4, strlen($secret));

        return str_repeat('•', max(0, strlen($secret) - $visible)) . substr($secret, -$visible);
    }
};
?>

<div class="w-full">
    @if ($success_message)
        <div class="mb-6 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <span class="text-sm font-medium">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="font-bold text-emerald-500 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Identitas Website
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Website / Acara</label>
                    <input type="text" wire:model="site_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Contoh: Gebyar Kemerdekaan RW 05">
                    @error('site_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tagline</label>
                    <input type="text" wire:model="tagline" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Contoh: Portal transparansi dan kegiatan warga">
                    @error('tagline') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Logo, Favicon, Banner, dan OG Image
            </h3>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-600">Logo <span class="text-slate-400">(WebP)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="max-h-full object-contain">
                        @elseif ($logoUrl)
                            <img src="{{ $logoUrl }}" class="max-h-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">Belum ada logo</span>
                        @endif
                    </div>
                    <input type="file" wire:model="logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                    <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                    @error('logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($logoUrl) <button type="button" wire:click="removeImage('logo')" class="mt-1 text-xs text-red-500 hover:underline">Hapus logo</button> @endif
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-600">Favicon <span class="text-slate-400">(png/ico/svg)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                        @if ($favicon)
                            <img src="{{ $favicon->temporaryUrl() }}" class="max-h-16 object-contain">
                        @elseif ($faviconUrl)
                            <img src="{{ $faviconUrl }}" class="max-h-16 object-contain">
                        @else
                            <span class="text-xs text-slate-400">Belum ada favicon</span>
                        @endif
                    </div>
                    <input type="file" wire:model="favicon" accept=".png,.ico,.svg,image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                    <div wire:loading wire:target="favicon" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                    @error('favicon') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($faviconUrl) <button type="button" wire:click="removeImage('favicon')" class="mt-1 text-xs text-red-500 hover:underline">Hapus favicon</button> @endif
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-600">Banner Hero <span class="text-slate-400">(WebP)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                        @if ($hero_banner)
                            <img src="{{ $hero_banner->temporaryUrl() }}" class="h-full w-full object-cover">
                        @elseif ($bannerUrl)
                            <img src="{{ $bannerUrl }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs text-slate-400">Belum ada banner</span>
                        @endif
                    </div>
                    <input type="file" wire:model="hero_banner" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                    <div wire:loading wire:target="hero_banner" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                    @error('hero_banner') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($bannerUrl) <button type="button" wire:click="removeImage('hero_banner')" class="mt-1 text-xs text-red-500 hover:underline">Hapus banner</button> @endif
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-600">OG Image <span class="text-slate-400">(jpg/png, ideal 1200x630)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                        @if ($og_image)
                            <img src="{{ $og_image->temporaryUrl() }}" class="h-full w-full object-cover">
                        @elseif ($ogImageUrl)
                            <img src="{{ $ogImageUrl }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs text-slate-400">Belum ada OG image</span>
                        @endif
                    </div>
                    <input type="file" wire:model="og_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                    <div wire:loading wire:target="og_image" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                    @error('og_image') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($ogImageUrl) <button type="button" wire:click="removeImage('og_image')" class="mt-1 text-xs text-red-500 hover:underline">Hapus OG image</button> @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> SEO &amp; Integrasi
            </h3>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Google Site Verification</label>
                <input type="text" wire:model="google_site_verification" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Contoh: abc123XYZ_google_verify_code">
                <p class="mt-2 text-xs text-slate-500">Isi hanya kode verifikasinya saja, tanpa tag meta penuh.</p>
                @error('google_site_verification') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Kontak &amp; Rekening Panitia
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">No. WhatsApp Utama</label>
                    <input type="text" wire:model="contact_whatsapp" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="0812xxxxxxx">
                    @error('contact_whatsapp') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Kontak (PJ)</label>
                    <input type="text" wire:model="contact_person" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Ketua Panitia">
                    @error('contact_person') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Bank</label>
                    <input type="text" wire:model="bank_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="BCA / BRI / Mandiri">
                    @error('bank_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nomor Rekening</label>
                    <input type="text" wire:model="bank_account_number" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="8800012345">
                    @error('bank_account_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Atas Nama</label>
                    <input type="text" wire:model="bank_account_holder" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Panitia RT 07">
                    @error('bank_account_holder') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-slate-600">Logo Bank <span class="text-slate-400">(tampil pada pilihan metode Transfer di Form Warga)</span></label>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-2">
                            @if ($bank_logo)
                                <img src="{{ $bank_logo->temporaryUrl() }}" class="max-h-full max-w-full object-contain">
                            @elseif ($bankLogoUrl)
                                <img src="{{ $bankLogoUrl }}" class="max-h-full max-w-full object-contain">
                            @else
                                <span class="text-center text-[11px] text-slate-400">Logo bank</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" wire:model="bank_logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                            <div wire:loading wire:target="bank_logo" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                            @error('bank_logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if ($bankLogoUrl) <button type="button" wire:click="removeImage('bank_logo')" class="mt-1 text-xs text-red-500 hover:underline">Hapus logo bank</button> @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Pembayaran QRIS (PayHook)
            </h3>

            <label class="flex items-start gap-3">
                <input type="checkbox" wire:model="payhook_enabled" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span class="text-sm text-slate-700">Aktifkan pembayaran iuran via <b>QRIS dinamis</b>. Saat aktif, warga bisa memilih metode "QRIS" di Form Warga dan langsung mendapat QR pembayaran.</span>
            </label>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Base URL API</label>
                    <input type="url" wire:model="payhook_base_url" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="https://cekbayar.com/api/v1">
                    <p class="mt-1 text-xs text-slate-400">Sertakan sampai <span class="font-mono">/api/v1</span>. Endpoint invoice dipanggil di <span class="font-mono">{base}/invoices</span>.</p>
                    @error('payhook_base_url') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">API Key (Production)</label>
                    <input type="password" wire:model="payhook_api_key" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="{{ $payhookApiKeyMasked ? 'Tersimpan: ' . $payhookApiKeyMasked : 'Tempel API key di sini' }}">
                    <p class="mt-1 text-xs text-slate-400">{{ $payhookApiKeyMasked ? 'Sudah tersimpan. Kosongkan untuk mempertahankan.' : 'Belum diisi.' }}</p>
                    @error('payhook_api_key') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Webhook Secret</label>
                    <input type="password" wire:model="payhook_webhook_secret" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="{{ $payhookSecretMasked ? 'Tersimpan: ' . $payhookSecretMasked : 'Secret untuk verifikasi webhook' }}">
                    <p class="mt-1 text-xs text-slate-400">{{ $payhookSecretMasked ? 'Sudah tersimpan. Kosongkan untuk mempertahankan.' : 'Harus sama dengan yang diset di dashboard PayHook.' }}</p>
                    @error('payhook_webhook_secret') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Channel Type</label>
                    <input type="text" wire:model="payhook_channel_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="qris">
                    @error('payhook_channel_type') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Callback / Webhook URL</label>
                    <input type="text" readonly value="{{ $payhookCallbackUrl }}" onclick="this.select()" class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-600 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    <p class="mt-1 text-xs text-slate-400">Salin URL ini ke setelan webhook/callback di dashboard PayHook, pakai <b>Webhook Secret</b> yang sama.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-slate-600">Logo QRIS <span class="text-slate-400">(tampil pada pilihan metode QRIS di Form Warga)</span></label>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-2">
                            @if ($qris_logo)
                                <img src="{{ $qris_logo->temporaryUrl() }}" class="max-h-full max-w-full object-contain">
                            @elseif ($qrisLogoUrl)
                                <img src="{{ $qrisLogoUrl }}" class="max-h-full max-w-full object-contain">
                            @else
                                <span class="text-center text-[11px] text-slate-400">Logo QRIS</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" wire:model="qris_logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                            <div wire:loading wire:target="qris_logo" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                            @error('qris_logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if ($qrisLogoUrl) <button type="button" wire:click="removeImage('qris_logo')" class="mt-1 text-xs text-red-500 hover:underline">Hapus logo QRIS</button> @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Syarat &amp; Ketentuan
            </h3>
            <label class="mb-1 block text-xs font-semibold text-slate-600">Isi Syarat &amp; Ketentuan</label>
            <textarea wire:model="terms_conditions" rows="10" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6 focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Tulis syarat & ketentuan di sini. Setiap baris baru akan tampil sebagai baris terpisah di halaman publik."></textarea>
            <p class="mt-1.5 text-xs text-slate-400">Tampil di halaman <span class="font-mono text-slate-500">/syarat-ketentuan</span> yang di-link dari Form Warga. Dikosongkan = memakai teks contoh (dummy) bawaan.</p>
            @error('terms_conditions') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                <span class="h-4 w-2 rounded bg-red-600"></span> Modal Welcome &amp; Musik
            </h3>

            <label class="flex items-start gap-3">
                <input type="checkbox" wire:model="welcome_enabled" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span class="text-sm text-slate-700">Tampilkan <b>modal welcome</b> di halaman warga. Saat pengunjung klik "Masuk", musik diputar otomatis &amp; berulang (looping).</span>
            </label>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Judul Welcome</label>
                    <input type="text" wire:model="welcome_title" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Selamat Datang">
                    @error('welcome_title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Pesan Welcome</label>
                    <textarea wire:model="welcome_message" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Selamat datang di portal warga..."></textarea>
                    @error('welcome_message') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Daftar Lagu (diputar berurutan &amp; looping)</p>

                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                    <input type="file" wire:model="music_files" multiple accept="audio/*,.mp3,.ogg,.wav,.m4a" class="w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                    <div wire:loading wire:target="music_files" class="mt-1 text-xs text-slate-400">Memproses file…</div>
                    @error('music_files.*') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-400">Format mp3/ogg/wav/m4a, maks 12MB/lagu. Bisa pilih beberapa sekaligus.</p>
                        <button type="button" wire:click="uploadMusic" wire:loading.attr="disabled" wire:target="uploadMusic,music_files" class="shrink-0 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">Unggah</button>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($tracks as $i => $track)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-xs font-bold text-red-700">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $track->title }}</p>
                                    <audio controls preload="none" src="{{ $track->url }}" class="mt-1 h-8 w-56 max-w-full"></audio>
                                </div>
                            </div>
                            <button type="button" wire:click="deleteMusic('{{ $track->id }}')" wire:confirm="Hapus lagu ini?" class="shrink-0 rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-slate-400">Belum ada lagu. Unggah lagu kemerdekaan (mis. Indonesia Raya) di atas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Pengaturan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
