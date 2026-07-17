<x-layouts.public title="Form Bazaar" :eventName="$event?->name">
    <div class="relative w-full min-w-0 max-w-full overflow-x-clip">
        <span class="merdeka-badge">Form Bazaar</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Daftar Lapak Bazaar</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-stone-500">Pendaftaran <span class="font-semibold text-stone-700">gratis</span> dan langsung dikonfirmasi, tidak perlu menunggu verifikasi panitia. Nomor HP yang diisi harus sama dengan yang dipakai saat mengisi Form Warga, karena satu keluarga hanya boleh membuka 1 lapak.</p>
    </div>

    @if (session('success_message'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 shadow-sm">
            <p class="text-sm font-semibold">{{ session('success_message') }}</p>
            <p class="mt-1 text-sm">Kode referensi lapak: <span class="font-bold">{{ session('reference_code') }}</span></p>
        </div>
    @endif

    @if (! $event)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
            <p class="text-sm font-semibold">Form belum dibuka karena belum ada acara aktif.</p>
        </div>
    @else
        @if ($event->bazaar_poster_url)
            <div class="mt-6 overflow-hidden rounded-2xl border border-stone-200 shadow-sm">
                <img src="{{ $event->bazaar_poster_url }}" alt="Poster Bazaar {{ $event->name }}" class="w-full h-auto">
            </div>
        @endif

        <section class="mt-6 w-full min-w-0 max-w-full overflow-x-clip">
            <div class="flex items-center gap-3">
                <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                <div>
                    <h2 class="text-lg font-black text-stone-900">Peserta Bazaar Terdaftar</h2>
                    <p class="text-sm text-stone-500">{{ $bazaarSubmissions->count() }} lapak sudah terdaftar.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($bazaarSubmissions as $submission)
                    <div class="merdeka-card group relative flex flex-col overflow-hidden p-5 pt-6">
                        <span class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-red-600 via-red-500 to-amber-400"></span>
                        <div class="flex items-center gap-3">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-md shadow-red-600/20"><x-icon name="cash" class="h-6 w-6" /></span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-black text-stone-900">{{ $submission->name }}</h3>
                                @if ($submission->resident_block)
                                    <p class="truncate text-xs text-stone-500">Blok {{ $submission->resident_block }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="mt-3.5 inline-flex w-fit items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700">{{ $submission->jenis_jualan }}</span>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Belum ada warga yang daftar bazaar. Jadilah yang pertama!</p>
                @endforelse
            </div>
        </section>

        <form method="POST" action="{{ route('public.bazaar-form.store') }}" class="mt-6 w-full min-w-0 max-w-full space-y-6 overflow-x-clip text-left">
            @csrf

            <section class="merdeka-card overflow-x-clip p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                    <div>
                        <h2 class="text-lg font-black text-stone-900">Data Pendaftar Lapak</h2>
                        <p class="text-sm text-stone-500">Boleh diisi oleh kepala keluarga maupun anggota keluarga yang sudah terdaftar di Data Warga.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Nama Pendaftar</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Budi Santoso">
                        @error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Blok / Rumah</label>
                        <input type="text" name="resident_block" value="{{ old('resident_block') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: A/01">
                        @error('resident_block') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">WhatsApp Aktif</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0812xxxxxxx">
                        <p class="mt-1 text-xs text-stone-400">Harus sama dengan No. HP yang diisi di Form Warga.</p>
                        @error('phone_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Jenis Jualan</label>
                        <input type="text" name="jenis_jualan" value="{{ old('jenis_jualan') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Es Jeruk">
                        <p class="mt-1 text-xs text-stone-400">Jenis jualan tidak boleh sama dengan warga lain (mis. sesama "Es Jeruk").</p>
                        @error('jenis_jualan') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            <button type="submit" class="w-full rounded-xl bg-red-700 px-5 py-3.5 text-sm font-bold text-white hover:bg-red-800 sm:w-auto">
                Daftar Lapak Bazaar
            </button>
        </form>
    @endif
</x-layouts.public>
