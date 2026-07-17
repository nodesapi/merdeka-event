<?php

use App\Models\Event;
use App\Support\ImageConverter;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?string $eventId = null;

    public $name = '';
    public $description = '';
    public $location = '';
    public $maps_url = '';
    public $start_date = '';
    public $end_date = '';
    public $registration_closes_at = '';
    public $lomba_registration_opens_at = '';
    public $status = 'active';
    public $recommended_contribution_amount = '';
    public $contribution_guidance = '';
    public $contribution_target_households = '';

    public $bazaar_poster;
    public ?string $bazaar_poster_path = null;
    public $bazaar_registration_open = true;

    public $success_message = '';

    public function mount()
    {
        $event = Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();

        if ($event) {
            $this->eventId = $event->id;
            $this->name = $event->name;
            $this->description = $event->description;
            $this->location = $event->location;
            $this->maps_url = $event->maps_url;
            $this->start_date = $event->start_date?->format('Y-m-d\TH:i');
            $this->end_date = $event->end_date?->format('Y-m-d\TH:i');
            $this->registration_closes_at = $event->registration_closes_at?->format('Y-m-d\TH:i');
            $this->lomba_registration_opens_at = $event->lomba_registration_opens_at?->format('Y-m-d\TH:i');
            $this->status = $event->status;
            $this->recommended_contribution_amount = $event->recommended_contribution_amount ? (string) (float) $event->recommended_contribution_amount : '';
            $this->contribution_guidance = $event->contribution_guidance;
            $this->contribution_target_households = $event->contribution_target_households ? (string) $event->contribution_target_households : '';
            $this->bazaar_poster_path = $event->bazaar_poster_path;
            $this->bazaar_registration_open = $event->bazaar_registration_open;
        }
    }

    protected function normalizeMoneyInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? '' : $digits;
    }

    public function save()
    {
        $this->recommended_contribution_amount = $this->normalizeMoneyInput($this->recommended_contribution_amount);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'maps_url' => 'nullable|url|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_closes_at' => 'nullable|date',
            'lomba_registration_opens_at' => 'nullable|date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'recommended_contribution_amount' => 'nullable|numeric|min:0',
            'contribution_guidance' => 'nullable|string',
            'contribution_target_households' => 'nullable|integer|min:0',
            'bazaar_poster' => 'nullable|image|max:4096',
            'bazaar_registration_open' => 'boolean',
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        unset($data['bazaar_poster']);
        $data['contribution_target_households'] = $data['contribution_target_households'] !== '' ? $data['contribution_target_households'] : null;

        if ($this->bazaar_poster) {
            if ($this->eventId) {
                ImageConverter::delete(Event::find($this->eventId)?->bazaar_poster_path);
            }
            $data['bazaar_poster_path'] = ImageConverter::storeAsWebp($this->bazaar_poster, 'events', 1920);
        }

        if ($this->eventId) {
            $event = Event::findOrFail($this->eventId);
            $event->update($data);
        } else {
            $data['slug'] = Str::slug($this->name) . '-' . now()->format('Y');
            $event = Event::create($data);
            $this->eventId = $event->id;
        }

        $this->reset('bazaar_poster');
        $this->bazaar_poster_path = $event->bazaar_poster_path;

        $this->success_message = 'Data acara berhasil disimpan.';
    }

    public function removeBazaarPoster()
    {
        if (! $this->eventId) {
            return;
        }

        $event = Event::findOrFail($this->eventId);
        ImageConverter::delete($event->bazaar_poster_path);
        $event->update(['bazaar_poster_path' => null]);
        $this->bazaar_poster_path = null;
        $this->success_message = 'Poster bazaar dihapus.';
    }

    public function dismissAlert()
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $preview = null;
        $durationText = '-';
        $statusTone = match ($this->status) {
            'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'completed' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'cancelled' => 'bg-red-50 text-red-700 ring-red-100',
            default => 'bg-amber-50 text-amber-700 ring-amber-100',
        };
        $statusLabel = match ($this->status) {
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => 'Draft',
        };

        if ($this->start_date && $this->end_date) {
            try {
                $start = \Illuminate\Support\Carbon::parse($this->start_date);
                $end = \Illuminate\Support\Carbon::parse($this->end_date);

                $preview = (new Event([
                    'start_date' => $start,
                    'end_date' => $end,
                ]))->schedule_label;

                $totalMinutes = (int) round(abs($start->diffInMinutes($end)));
                $days = intdiv($totalMinutes, 1440);
                $hours = intdiv($totalMinutes % 1440, 60);

                $durationText = $days > 0
                    ? $days . ' hari ' . $hours . ' jam'
                    : max($hours, 1) . ' jam';
            } catch (\Throwable $e) {
                $preview = null;
                $durationText = '-';
            }
        }

        return [
            'schedulePreview' => $preview,
            'durationText' => $durationText,
            'statusTone' => $statusTone,
            'statusLabel' => $statusLabel,
            'bazaarPosterUrl' => $this->bazaar_poster_path ? '/storage/' . ltrim($this->bazaar_poster_path, '/') : null,
        ];
    }
};
?>

<div class="w-full">
    @if ($success_message)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
            <div>
                <p class="text-sm font-semibold">Perubahan tersimpan</p>
                <p class="mt-1 text-sm">{{ $success_message }}</p>
            </div>
            <button wire:click="dismissAlert" class="text-lg font-bold leading-none text-emerald-500 transition hover:text-emerald-800" aria-label="Tutup notifikasi">&times;</button>
        </div>
    @endif

    <div>
        <section class="space-y-6">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-gradient-to-r from-red-600 via-red-500 to-orange-400 px-5 py-5 text-white sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-red-100">Pengaturan Acara</p>
                    <h3 class="mt-2 text-2xl font-bold">Atur acara utama dengan tampilan lebar</h3>
                    <p class="mt-2 max-w-2xl text-sm text-red-50">Form ini sudah dibuat penuh agar lebih nyaman mengelola nama acara, jadwal, lokasi, dan status dari desktop maupun mobile.</p>
                </div>

                <form wire:submit.prevent="save" class="space-y-6 p-5 sm:p-6 lg:p-8">
                    <div class="grid gap-6 xl:grid-cols-2">
                        <div class="space-y-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Informasi utama acara</h4>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama Acara</label>
                                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: Gebyar Kemerdekaan RW 05">
                                        @error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Deskripsi</label>
                                        <textarea wire:model="description" rows="6" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Agenda bersama warga untuk lomba 17-an, malam puncak, hiburan, dan pelaporan dana kegiatan."></textarea>
                                        @error('description') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Lokasi & tautan</h4>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Lokasi</label>
                                        <input type="text" wire:model="location" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Lapangan RT 07">
                                        @error('location') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Link Google Maps</label>
                                        <input type="text" wire:model="maps_url" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="https://maps.app.goo.gl/...">
                                        @error('maps_url') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Jadwal pelaksanaan</h4>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Mulai</label>
                                            <div wire:ignore>
                                                <input type="hidden" wire:model.live="start_date" value="{{ $start_date }}" data-custom-datetime data-custom-datetime-placeholder="Pilih tanggal mulai">
                                            </div>
                                            @error('start_date') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Selesai</label>
                                            <div wire:ignore>
                                                <input type="hidden" wire:model.live="end_date" value="{{ $end_date }}" data-custom-datetime data-custom-datetime-placeholder="Pilih tanggal selesai">
                                            </div>
                                            @error('end_date') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Batas Pendaftaran &amp; Pengumpulan Dana</label>
                                        <div wire:ignore>
                                            <input type="hidden" wire:model.live="registration_closes_at" value="{{ $registration_closes_at }}" data-custom-datetime data-custom-datetime-placeholder="Pilih batas waktu (opsional)">
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-500">Ditampilkan sebagai hitung mundur di halaman utama warga. Kosongkan jika tidak ingin menampilkan batas waktu.</p>
                                        @error('registration_closes_at') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tanggal Buka Pendaftaran Lomba</label>
                                        <div wire:ignore>
                                            <input type="hidden" wire:model.live="lomba_registration_opens_at" value="{{ $lomba_registration_opens_at }}" data-custom-datetime data-custom-datetime-placeholder="Pilih tanggal buka (opsional)">
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-500">Sebelum tanggal ini, halaman Daftar Lomba akan menampilkan pesan bahwa pendaftaran belum dibuka. Kosongkan jika pendaftaran lomba langsung dibuka.</p>
                                        @error('lomba_registration_opens_at') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Preview tampil ke warga</p>
                                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $schedulePreview ?: 'Lengkapi jadwal untuk melihat preview' }}</p>
                                        <p class="mt-2 text-sm text-slate-600">Durasi: <span class="font-semibold text-slate-900">{{ $durationText }}</span></p>
                                        <p class="mt-2 text-xs text-slate-500">Untuk acara 2 hari seperti 16-17 Agustus, isi tanggal mulai dan selesai sesuai rentang aslinya.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Publikasi & kontrol</h4>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status Acara</label>
                                        <select wire:model="status" data-custom-select class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100">
                                            <option value="draft">Draft</option>
                                            <option value="active">Aktif (tampil di halaman warga)</option>
                                            <option value="completed">Selesai</option>
                                            <option value="cancelled">Dibatalkan</option>
                                        </select>
                                        @error('status') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status saat ini</p>
                                            <div class="mt-2 inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $statusTone }}">
                                                {{ $statusLabel }}
                                            </div>
                                        </div>

                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                                            Simpan Acara
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Iuran & panduan warga</h4>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nominal Rekomendasi Iuran</label>
                                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-3" data-rupiah-input>
                                            <div class="relative">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">Rp</span>
                                                <input type="text" value="{{ $recommended_contribution_amount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="50.000">
                                                <input type="hidden" wire:model.live="recommended_contribution_amount" value="{{ $recommended_contribution_amount }}" data-rupiah-hidden>
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500">Format otomatis Indonesia aktif. Ketik 50000, tampilnya langsung 50.000.</p>
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-500">Nilai ini tampil sebagai acuan di form warga, tetapi warga tetap bisa memberi tambahan sukarela, donasi, atau sponsor.</p>
                                        @error('recommended_contribution_amount') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Target Jumlah Rumah/KK <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
                                        <input type="number" min="0" wire:model="contribution_target_households" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: 200">
                                        <p class="mt-1.5 text-xs text-slate-500">Dipakai untuk menghitung target total dana iuran (Nominal Iuran &times; Target Rumah), ditampilkan di halaman RAB dan transparansi dana.</p>
                                        @error('contribution_target_households') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Panduan Singkat untuk Warga</label>
                                        <textarea wire:model="contribution_guidance" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: Iuran rekomendasi per keluarga Rp50.000. Warga boleh menambah kontribusi sukarela atau sponsor untuk hadiah lomba."></textarea>
                                        @error('contribution_guidance') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="mb-5 flex items-center gap-2">
                                    <span class="h-4 w-1.5 rounded-full bg-red-600"></span>
                                    <h4 class="text-base font-semibold text-slate-900">Promosi Bazaar</h4>
                                </div>

                                <label class="mb-5 flex items-start gap-3">
                                    <input type="checkbox" wire:model="bazaar_registration_open" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span class="text-sm text-slate-700">Buka pendaftaran lapak bazaar untuk warga. Matikan kapan saja untuk menutup sementara (mis. kuota penuh atau belum waktunya) tanpa perlu mengubah data lain.</span>
                                </label>

                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Poster Bazaar</label>
                                <div class="mb-2 flex h-40 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                                    @if ($bazaar_poster)
                                        <img src="{{ $bazaar_poster->temporaryUrl() }}" class="h-full w-full object-contain">
                                    @elseif ($bazaarPosterUrl)
                                        <img src="{{ $bazaarPosterUrl }}" class="h-full w-full object-contain">
                                    @else
                                        <span class="text-xs text-slate-400">Belum ada poster</span>
                                    @endif
                                </div>
                                <input type="file" wire:model="bazaar_poster" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-500 shadow-sm outline-none file:mr-2 file:rounded-md file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:font-medium file:text-red-700">
                                <div wire:loading wire:target="bazaar_poster" class="mt-1 text-xs text-slate-400">Mengunggah...</div>
                                @error('bazaar_poster') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                <p class="mt-1.5 text-xs text-slate-500">Ditampilkan di halaman Form Bazaar publik. Ganti kapan saja kalau ada revisi desain.</p>
                                @if ($bazaarPosterUrl)
                                    <button type="button" wire:click="removeBazaarPoster" class="mt-2 text-xs text-red-500 hover:underline">Hapus poster</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
