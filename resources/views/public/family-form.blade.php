<x-layouts.public title="Form Warga" :eventName="$event?->name">
    <div>
        <span class="merdeka-badge">Form Warga</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Form Kontribusi & Pendaftaran Keluarga</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-stone-500">Warga dapat mengisi data keluarga, kontribusi iuran atau donasi, sekaligus mendaftarkan anak yang ingin ikut lomba agar panitia menerima data dengan rapi.</p>
    </div>

    @if (session('success_message'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 shadow-sm">
            <p class="text-sm font-semibold">{{ session('success_message') }}</p>
            <p class="mt-1 text-sm">Nomor referensi keluarga: <span class="font-bold">{{ session('reference_code') }}</span></p>
        </div>
    @endif

    @if (! $event)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
            <p class="text-sm font-semibold">Form belum dibuka karena belum ada acara aktif.</p>
        </div>
    @else
        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="merdeka-card p-5 lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wide text-red-700">Acara Aktif</p>
                <h2 class="mt-2 text-xl font-black text-stone-900">{{ $event->name }}</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">{{ $event->description }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-stone-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Jadwal</p>
                        <p class="mt-1 text-sm font-semibold text-stone-900">{{ $event->schedule_label }}</p>
                    </div>
                    <div class="rounded-xl bg-stone-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Lokasi</p>
                        <p class="mt-1 text-sm font-semibold text-stone-900">{{ $event->location }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-700 p-5 text-white shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-red-100">Nominal Rekomendasi</p>
                <p class="mt-2 text-3xl font-extrabold">Rp{{ number_format($event->recommended_contribution_amount ?? 0, 0, ',', '.') }}</p>
                <p class="mt-3 text-sm leading-6 text-red-50">{{ $event->contribution_guidance ?: 'Panitia menetapkan nominal rekomendasi sebagai acuan, namun warga tetap dapat memberi tambahan sukarela, donasi, atau sponsor.' }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('public.family-form.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 text-left">
            @csrf

            <section class="merdeka-card p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                    <div>
                        <h2 class="text-lg font-black text-stone-900">1. Data Keluarga</h2>
                        <p class="text-sm text-stone-500">Isi data kepala keluarga sebagai kontak utama panitia.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Nama Kepala Keluarga</label>
                        <input type="text" name="head_of_family_name" value="{{ old('head_of_family_name') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Budi Santoso">
                        @error('head_of_family_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Blok / Rumah</label>
                        <input type="text" name="resident_block" value="{{ old('resident_block') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: A/01">
                        @error('resident_block') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">WhatsApp Aktif</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0812xxxxxxx">
                        @error('phone_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="opsional@email.com">
                        @error('email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Catatan Keluarga</label>
                        <textarea name="notes" rows="3" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Opsional: keterangan tambahan untuk panitia">{{ old('notes') }}</textarea>
                        @error('notes') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            @php
                $iuranAmount = (string) (int) round((float) old('contribution_iuran_amount', $event->recommended_contribution_amount));
                $tambahanAmount = (string) (int) round((float) old('contribution_tambahan_amount', 0));
                $donasiAmount = (string) (int) round((float) old('contribution_donasi_amount', 0));
                $sponsorAmount = (string) (int) round((float) old('contribution_sponsor_amount', 0));
            @endphp

            <section class="merdeka-card p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                    <div>
                        <h2 class="text-lg font-black text-stone-900">2. Kontribusi Dana</h2>
                        <p class="text-sm text-stone-500">Warga dapat mengisi iuran sesuai rekomendasi, sekaligus menambahkan kontribusi sukarela lain bila berkenan.</p>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-red-100 bg-red-50/70 px-4 py-3 text-sm text-red-800">
                    Ketik nominal seperti biasa, nanti otomatis dirapikan ke format Indonesia. Contoh: 50000 akan tampil menjadi 50.000.
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-2">
                    <div class="rounded-2xl border border-stone-200 bg-gradient-to-br from-white to-stone-50 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Iuran Warga</label>
                                <p class="mt-1 text-sm text-stone-500">Nominal utama sesuai rekomendasi panitia.</p>
                            </div>
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-red-700">Utama</span>
                        </div>
                        <div class="mt-3" data-rupiah-input>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Nominal</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-stone-500">Rp</span>
                                <input type="text" value="{{ $iuranAmount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-xl border border-stone-300 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-stone-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="50.000">
                                <input type="hidden" name="contribution_iuran_amount" value="{{ $iuranAmount }}" data-rupiah-hidden>
                            </div>
                        </div>
                        <input type="text" name="contribution_iuran_note" value="{{ old('contribution_iuran_note') }}" class="mt-3 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Iuran keluarga bulan Agustus">
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-gradient-to-br from-white to-stone-50 p-4 shadow-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Tambahan Sukarela</label>
                            <p class="mt-1 text-sm text-stone-500">Bisa dipakai untuk hadiah lomba atau kebutuhan tambahan acara.</p>
                        </div>
                        <div class="mt-3" data-rupiah-input>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Nominal</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-stone-500">Rp</span>
                                <input type="text" value="{{ $tambahanAmount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-xl border border-stone-300 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-stone-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0">
                                <input type="hidden" name="contribution_tambahan_amount" value="{{ $tambahanAmount }}" data-rupiah-hidden>
                            </div>
                        </div>
                        <input type="text" name="contribution_tambahan_note" value="{{ old('contribution_tambahan_note') }}" class="mt-3 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Tambahan untuk hadiah lomba">
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-gradient-to-br from-white to-stone-50 p-4 shadow-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Donasi</label>
                            <p class="mt-1 text-sm text-stone-500">Untuk bantuan umum agar kegiatan warga lebih leluasa.</p>
                        </div>
                        <div class="mt-3" data-rupiah-input>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Nominal</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-stone-500">Rp</span>
                                <input type="text" value="{{ $donasiAmount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-xl border border-stone-300 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-stone-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0">
                                <input type="hidden" name="contribution_donasi_amount" value="{{ $donasiAmount }}" data-rupiah-hidden>
                            </div>
                        </div>
                        <input type="text" name="contribution_donasi_note" value="{{ old('contribution_donasi_note') }}" class="mt-3 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Donasi umum acara warga">
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-gradient-to-br from-white to-stone-50 p-4 shadow-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Sponsor</label>
                            <p class="mt-1 text-sm text-stone-500">Cocok untuk dukungan usaha, hadiah, atau kontribusi khusus lainnya.</p>
                        </div>
                        <div class="mt-3" data-rupiah-input>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Nominal</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-stone-500">Rp</span>
                                <input type="text" value="{{ $sponsorAmount }}" inputmode="numeric" autocomplete="off" data-rupiah-visible class="w-full rounded-xl border border-stone-300 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-stone-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0">
                                <input type="hidden" name="contribution_sponsor_amount" value="{{ $sponsorAmount }}" data-rupiah-hidden>
                            </div>
                        </div>
                        <input type="text" name="contribution_sponsor_label" value="{{ old('contribution_sponsor_label') }}" class="mt-3 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Sponsor hadiah atau nama usaha">
                        <input type="text" name="contribution_sponsor_note" value="{{ old('contribution_sponsor_note') }}" class="mt-3 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Catatan sponsor">
                    </div>
                </div>

                @error('contribution_iuran_amount') <span class="mt-3 block text-xs text-red-600">{{ $message }}</span> @enderror

                @if ($site?->bank_account_number)
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-center shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Rekening Tujuan Transfer</p>
                        <p class="mt-2 text-lg font-extrabold text-emerald-950">{{ $site->bank_name ?: 'Bank' }} · {{ $site->bank_account_number }}</p>
                        @if ($site->bank_account_holder)
                            <p class="mt-1 text-sm text-emerald-800">a/n {{ $site->bank_account_holder }}</p>
                        @endif
                        <p class="mt-3 text-sm leading-6 text-emerald-800">
                            Bila memilih metode <span class="font-semibold">transfer</span>, silakan kirim ke rekening di atas lalu unggah bukti pembayarannya di form ini.
                        </p>
                    </div>
                @endif

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Metode Pembayaran</label>
                        <select name="payment_method" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                            <option value="">Pilih metode</option>
                            <option value="transfer" @selected(old('payment_method') === 'transfer')>Transfer</option>
                            <option value="cash" @selected(old('payment_method') === 'cash')>Tunai</option>
                            <option value="other" @selected(old('payment_method') === 'other')>Lainnya</option>
                        </select>
                        @error('payment_method') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Bukti Pembayaran</label>
                        <div class="mt-2 w-full max-w-full overflow-hidden rounded-xl border border-stone-300 bg-white">
                            <input type="file" name="proof_file" class="block w-full max-w-full px-4 py-3 text-sm focus:outline-none">
                        </div>
                        @error('proof_file') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Catatan Pembayaran</label>
                        <textarea name="payment_notes" rows="3" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: transfer dari rekening BCA, atau akan diserahkan tunai saat rapat">{{ old('payment_notes') }}</textarea>
                        @error('payment_notes') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            <section class="merdeka-card p-5 sm:p-6" data-family-form>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                        <div>
                            <h2 class="text-lg font-black text-stone-900">3. Daftar Anggota Keluarga</h2>
                            <p class="text-sm text-stone-500">Tambahkan anggota keluarga. Bila anggota adalah anak dan ingin ikut lomba, pilih lomba yang diinginkan.</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-xl bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800" data-add-member>Tambah Anggota</button>
                </div>

                <div class="mt-5 space-y-4" data-members-wrapper>
                    @php
                        $oldMembers = old('members', [['name' => '', 'relationship' => 'ayah', 'age' => '', 'gender' => '', 'competition_id' => '', 'notes' => '']]);
                    @endphp

                    @foreach ($oldMembers as $index => $member)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4" data-member-item>
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-stone-900">Anggota Keluarga <span data-member-number>{{ $index + 1 }}</span></p>
                                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-800" data-remove-member>Hapus</button>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Nama</label>
                                    <input type="text" name="members[{{ $index }}][name]" value="{{ $member['name'] ?? '' }}" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    @error("members.$index.name") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Hubungan</label>
                                    <select name="members[{{ $index }}][relationship]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" data-relationship-select>
                                        <option value="ayah" @selected(($member['relationship'] ?? '') === 'ayah')>Ayah</option>
                                        <option value="ibu" @selected(($member['relationship'] ?? '') === 'ibu')>Ibu</option>
                                        <option value="anak" @selected(($member['relationship'] ?? '') === 'anak')>Anak</option>
                                        <option value="lainnya" @selected(($member['relationship'] ?? '') === 'lainnya')>Lainnya</option>
                                    </select>
                                    @error("members.$index.relationship") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Usia</label>
                                    <input type="number" name="members[{{ $index }}][age]" value="{{ $member['age'] ?? '' }}" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: 10">
                                    @error("members.$index.age") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Gender</label>
                                    <select name="members[{{ $index }}][gender]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                                        <option value="">Pilih</option>
                                        <option value="L" @selected(($member['gender'] ?? '') === 'L')>Laki-laki</option>
                                        <option value="P" @selected(($member['gender'] ?? '') === 'P')>Perempuan</option>
                                    </select>
                                    @error("members.$index.gender") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2" data-competition-field @if (($member['relationship'] ?? '') !== 'anak') style="display:none" @endif>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Lomba yang Diikuti Anak</label>
                                    <select name="members[{{ $index }}][competition_id]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                                        <option value="">Tidak ikut lomba</option>
                                        @foreach ($competitions as $competition)
                                            <option value="{{ $competition->id }}" @selected(($member['competition_id'] ?? '') === $competition->id)>{{ $competition->name }} - {{ $competition->target_participants }}</option>
                                        @endforeach
                                    </select>
                                    @error("members.$index.competition_id") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Catatan Anggota</label>
                                    <input type="text" name="members[{{ $index }}][notes]" value="{{ $member['notes'] ?? '' }}" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Opsional: misalnya butuh perhatian khusus, lomba tertentu, dll">
                                    @error("members.$index.notes") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('members') <span class="mt-2 block text-xs text-red-600">{{ $message }}</span> @enderror

                <template id="member-template">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4" data-member-item>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold text-stone-900">Anggota Keluarga <span data-member-number></span></p>
                            <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-800" data-remove-member>Hapus</button>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Nama</label>
                                <input type="text" name="members[__INDEX__][name]" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Hubungan</label>
                                <select name="members[__INDEX__][relationship]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" data-relationship-select>
                                    <option value="ayah">Ayah</option>
                                    <option value="ibu">Ibu</option>
                                    <option value="anak" selected>Anak</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Usia</label>
                                <input type="number" name="members[__INDEX__][age]" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Gender</label>
                                <select name="members[__INDEX__][gender]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    <option value="">Pilih</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="md:col-span-2" data-competition-field>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Lomba yang Diikuti Anak</label>
                                <select name="members[__INDEX__][competition_id]" data-custom-select class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    <option value="">Tidak ikut lomba</option>
                                    @foreach ($competitions as $competition)
                                        <option value="{{ $competition->id }}">{{ $competition->name }} - {{ $competition->target_participants }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">Catatan Anggota</label>
                                <input type="text" name="members[__INDEX__][notes]" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-red-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-red-800">
                    Kirim Form Keluarga
                </button>
            </div>
        </form>

        <script>
            (function () {
                const formRoot = document.querySelector('[data-family-form]');
                if (!formRoot) return;

                const wrapper = formRoot.querySelector('[data-members-wrapper]');
                const addButton = formRoot.querySelector('[data-add-member]');
                const template = document.getElementById('member-template');

                const updateNumbers = () => {
                    wrapper.querySelectorAll('[data-member-item]').forEach((item, index) => {
                        const number = item.querySelector('[data-member-number]');
                        if (number) number.textContent = String(index + 1);
                    });
                };

                const bindItem = (item) => {
                    const removeButton = item.querySelector('[data-remove-member]');
                    const relationshipSelect = item.querySelector('[data-relationship-select]');
                    const competitionField = item.querySelector('[data-competition-field]');

                    const toggleCompetition = () => {
                        const isChild = relationshipSelect?.value === 'anak';
                        if (competitionField) {
                            competitionField.style.display = isChild ? '' : 'none';
                            if (!isChild) {
                                const select = competitionField.querySelector('select');
                                if (select) select.value = '';
                            }
                        }
                    };

                    relationshipSelect?.addEventListener('change', toggleCompetition);
                    removeButton?.addEventListener('click', () => {
                        if (wrapper.querySelectorAll('[data-member-item]').length === 1) return;
                        item.remove();
                        updateNumbers();
                    });

                    toggleCompetition();
                };

                addButton?.addEventListener('click', () => {
                    const index = wrapper.querySelectorAll('[data-member-item]').length;
                    const html = template.innerHTML.replaceAll('__INDEX__', String(index));
                    wrapper.insertAdjacentHTML('beforeend', html);
                    bindItem(wrapper.lastElementChild);
                    updateNumbers();
                });

                wrapper.querySelectorAll('[data-member-item]').forEach(bindItem);
                updateNumbers();
            })();
        </script>
    @endif
</x-layouts.public>
