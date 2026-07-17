<x-layouts.public title="Form Bazaar" :eventName="$event?->name">
    <div class="relative w-full min-w-0 max-w-full overflow-x-clip">
        <span class="merdeka-badge">Form Bazaar</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Daftar Lapak Bazaar</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-stone-500">Pendaftaran <span class="font-semibold text-stone-700">gratis</span> dan langsung dikonfirmasi, tidak perlu menunggu verifikasi panitia. Nomor HP yang diisi harus sama dengan yang dipakai saat mengisi Form Warga, karena satu keluarga hanya boleh membuka 1 lapak. Kuota lapak <span class="font-semibold text-stone-700">terbatas {{ $bazaarSlotLimit ?? 15 }} lapak</span> — siapa cepat, dia dapat!</p>
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
            <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:gap-3 sm:text-left">
                <span class="hidden h-7 w-1.5 rounded-full bg-red-600 sm:block"></span>
                <div>
                    <h2 class="text-lg font-black text-stone-900">Peserta Bazaar Terdaftar</h2>
                    <p class="text-sm text-stone-500">{{ $bazaarSubmissions->count() }} dari {{ $bazaarSlotLimit }} lapak terisi @if ($bazaarSlotsRemaining > 0) &middot; sisa <span class="font-bold text-red-700">{{ $bazaarSlotsRemaining }}</span> slot @else &middot; <span class="font-bold text-red-700">kuota penuh</span> @endif.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @forelse ($bazaarSubmissions as $submission)
                    <div class="merdeka-card group relative flex flex-col items-center overflow-hidden p-3.5 pt-5 text-center sm:p-5 sm:pt-6">
                        <span class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-red-600"></span>
                        <x-icon name="storefront" class="pointer-events-none absolute -right-3 -top-2 h-16 w-16 text-red-950/[0.05] sm:h-20 sm:w-20" />
                        <div class="relative flex flex-col items-center">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-red-600 text-white shadow-md shadow-red-600/20 sm:h-12 sm:w-12 sm:rounded-2xl"><x-icon name="storefront" class="h-5 w-5 sm:h-6 sm:w-6" /></span>
                            <h3 class="mt-2.5 line-clamp-1 text-sm font-black text-stone-900 sm:mt-3 sm:text-base">{{ $submission->name }}</h3>
                            @if ($submission->resident_block)
                                <p class="truncate text-[11px] text-stone-500 sm:text-xs">Blok {{ $submission->resident_block }}</p>
                            @endif
                            <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1 sm:mt-3">
                                @foreach ($submission->jenis_jualan_items as $item)
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 sm:px-2.5 sm:py-1 sm:text-[11px]">{{ $item }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-2 text-sm text-stone-500 lg:col-span-3">Belum ada warga yang daftar bazaar. Jadilah yang pertama!</p>
                @endforelse
            </div>
        </section>

        @if ($bazaarSlotsRemaining > 0)
            <form method="POST" action="{{ route('public.bazaar-form.store') }}" class="mt-6 w-full min-w-0 max-w-full space-y-6 overflow-x-clip text-left">
                @csrf

                <section class="merdeka-card overflow-x-clip p-5 sm:p-6">
                    <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:gap-3 sm:text-left">
                        <span class="hidden h-7 w-1.5 rounded-full bg-red-600 sm:block"></span>
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
                            <input type="text" name="jenis_jualan" value="{{ old('jenis_jualan') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Es Jeruk, Kerupuk, Basreng">
                            <p class="mt-1 text-xs text-stone-400">Jualan lebih dari 1 macam? Pisahkan dengan koma (,). Jenis jualan tidak boleh sama dengan warga lain (mis. sesama "Es Jeruk").</p>
                            @error('jenis_jualan') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <button type="submit" class="w-full rounded-xl bg-red-700 px-5 py-3.5 text-sm font-bold text-white hover:bg-red-800 sm:w-auto">
                    Daftar Lapak Bazaar
                </button>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
                <p class="text-sm font-semibold">Kuota {{ $bazaarSlotLimit }} lapak bazaar sudah penuh. Terima kasih atas antusiasnya — sampai jumpa di acara berikutnya!</p>
            </div>
        @endif
    @endif
</x-layouts.public>
