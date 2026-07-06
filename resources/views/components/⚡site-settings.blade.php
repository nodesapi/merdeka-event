<?php

use App\Models\SiteSetting;
use App\Support\ImageConverter;
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

    public $logo;
    public $favicon;
    public $hero_banner;
    public $og_image;

    public ?string $logo_path = null;
    public ?string $favicon_path = null;
    public ?string $hero_banner_path = null;
    public ?string $og_image_path = null;

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
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
        $this->og_image_path = $setting->og_image_path;
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
            'logo' => 'nullable|image|max:4096',
            'favicon' => 'nullable|max:1024|mimes:png,ico,svg,jpg,jpeg',
            'hero_banner' => 'nullable|image|max:8192',
            'og_image' => 'nullable|image|max:4096|mimes:jpg,jpeg,png',
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
        ];

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

        $setting->update($data);

        $this->reset(['logo', 'favicon', 'hero_banner', 'og_image']);
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
        $this->og_image_path = $setting->og_image_path;
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

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        return [
            'logoUrl' => $this->logo_path ? '/storage/' . ltrim($this->logo_path, '/') : null,
            'faviconUrl' => $this->favicon_path ? '/storage/' . ltrim($this->favicon_path, '/') : null,
            'bannerUrl' => $this->hero_banner_path ? '/storage/' . ltrim($this->hero_banner_path, '/') : null,
            'ogImageUrl' => $this->og_image_path ? '/storage/' . ltrim($this->og_image_path, '/') : null,
        ];
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
