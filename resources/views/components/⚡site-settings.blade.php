<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SiteSetting;
use App\Support\ImageConverter;

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

    // Uploads
    public $logo;
    public $favicon;
    public $hero_banner;

    // Existing stored paths (for preview + deletion)
    public ?string $logo_path = null;
    public ?string $favicon_path = null;
    public ?string $hero_banner_path = null;

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
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
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
            'logo' => 'nullable|image|max:4096',
            'favicon' => 'nullable|max:1024|mimes:png,ico,svg,jpg,jpeg',
            'hero_banner' => 'nullable|image|max:8192',
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

        $setting->update($data);

        $this->reset(['logo', 'favicon', 'hero_banner']);
        $this->logo_path = $setting->logo_path;
        $this->favicon_path = $setting->favicon_path;
        $this->hero_banner_path = $setting->hero_banner_path;
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
        ];
    }
};
?>

<div class="w-full">
    @if ($success_message)
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center justify-between shadow-sm">
            <span class="font-medium text-sm">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="text-emerald-500 hover:text-emerald-800 font-bold">&times;</button>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Identity -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span> Identitas Website
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Website / Acara</label>
                    <input type="text" wire:model="site_name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Gebyar Kemerdekaan RW 05">
                    @error('site_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tagline</label>
                    <input type="text" wire:model="tagline" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Portal transparansi dan kegiatan warga">
                    @error('tagline') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Media -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span> Logo, Favicon &amp; Banner
            </h3>
            <div class="grid gap-6 md:grid-cols-3">
                {{-- Logo --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Logo <span class="text-slate-400">(→ WebP)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 overflow-hidden">
                        @if ($logo) <img src="{{ $logo->temporaryUrl() }}" class="max-h-full object-contain">
                        @elseif ($logoUrl) <img src="{{ $logoUrl }}" class="max-h-full object-contain">
                        @else <span class="text-xs text-slate-400">Belum ada logo</span> @endif
                    </div>
                    <input type="file" wire:model="logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-red-700 file:font-medium">
                    <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
                    @error('logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($logoUrl) <button type="button" wire:click="removeImage('logo')" class="mt-1 text-xs text-red-500 hover:underline">Hapus logo</button> @endif
                </div>

                {{-- Favicon --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Favicon <span class="text-slate-400">(png/ico/svg)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 overflow-hidden">
                        @if ($favicon) <img src="{{ $favicon->temporaryUrl() }}" class="max-h-16 object-contain">
                        @elseif ($faviconUrl) <img src="{{ $faviconUrl }}" class="max-h-16 object-contain">
                        @else <span class="text-xs text-slate-400">Belum ada favicon</span> @endif
                    </div>
                    <input type="file" wire:model="favicon" accept=".png,.ico,.svg,image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-red-700 file:font-medium">
                    <div wire:loading wire:target="favicon" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
                    @error('favicon') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($faviconUrl) <button type="button" wire:click="removeImage('favicon')" class="mt-1 text-xs text-red-500 hover:underline">Hapus favicon</button> @endif
                </div>

                {{-- Banner --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Banner Hero <span class="text-slate-400">(→ WebP)</span></label>
                    <div class="mb-2 flex h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 overflow-hidden">
                        @if ($hero_banner) <img src="{{ $hero_banner->temporaryUrl() }}" class="h-full w-full object-cover">
                        @elseif ($bannerUrl) <img src="{{ $bannerUrl }}" class="h-full w-full object-cover">
                        @else <span class="text-xs text-slate-400">Belum ada banner</span> @endif
                    </div>
                    <input type="file" wire:model="hero_banner" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-red-700 file:font-medium">
                    <div wire:loading wire:target="hero_banner" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
                    @error('hero_banner') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if ($bannerUrl) <button type="button" wire:click="removeImage('hero_banner')" class="mt-1 text-xs text-red-500 hover:underline">Hapus banner</button> @endif
                </div>
            </div>
        </div>

        <!-- Contact & bank -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-semibold text-base text-slate-900 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                <span class="w-2 h-4 bg-red-600 rounded"></span> Kontak &amp; Rekening Panitia
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">No. WhatsApp Utama</label>
                    <input type="text" wire:model="contact_whatsapp" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="0812xxxxxxx">
                    @error('contact_whatsapp') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Kontak (PJ)</label>
                    <input type="text" wire:model="contact_person" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Ketua Panitia">
                    @error('contact_person') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Bank</label>
                    <input type="text" wire:model="bank_name" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="BCA / BRI / Mandiri">
                    @error('bank_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor Rekening</label>
                    <input type="text" wire:model="bank_account_number" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="8800012345">
                    @error('bank_account_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Atas Nama</label>
                    <input type="text" wire:model="bank_account_holder" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="Panitia RT 07">
                    @error('bank_account_holder') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold shadow-sm disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Pengaturan</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </button>
        </div>
    </form>
</div>
