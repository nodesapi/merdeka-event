<x-layouts.public title="Daftar Lomba" :eventName="$event?->name">
    <div class="family-form-shell relative w-full min-w-0 max-w-full overflow-x-clip">
        <span class="merdeka-badge">Daftar Lomba</span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-stone-900">Pendaftaran Lomba</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-stone-500">Masukkan <span class="font-semibold text-stone-700">No Daftar</span> yang kamu dapat dari Form Warga. Nama, umur, dan gender akan muncul otomatis — tinggal centang lomba yang ingin diikuti. Kamu bisa memilih beberapa lomba sekaligus.</p>
    </div>

    @if (session('success_message'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 shadow-sm">
            <p class="text-sm font-semibold">{{ session('success_message') }}</p>
        </div>
    @endif

    @if (! $event)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
            <p class="text-sm font-semibold">Pendaftaran lomba belum dibuka karena belum ada acara aktif.</p>
        </div>
    @elseif (! $event->isLombaRegistrationOpen())
        <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
            <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
                <div>
                    <p class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-amber-700 sm:justify-start">
                        <x-icon name="clock" class="h-4 w-4" /> Pendaftaran Lomba Belum Dibuka
                    </p>
                    <p class="mt-1 text-sm text-stone-600">Pendaftaran dibuka mulai {{ $event->lomba_registration_opens_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB.</p>
                </div>
                <div id="lomba-open-countdown" data-target="{{ $event->lomba_registration_opens_at->timestamp }}" class="grid grid-cols-4 gap-2 text-center">
                    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'mins' => 'Menit', 'secs' => 'Detik'] as $key => $label)
                        <div class="rounded-xl bg-white/70 px-3 py-2">
                            <span data-cd="{{ $key }}" class="block text-xl font-black tabular-nums text-amber-800 sm:text-2xl">00</span>
                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide text-amber-700/70">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <script>
            (function () {
                var el = document.getElementById('lomba-open-countdown');
                if (!el) return;
                var target = parseInt(el.dataset.target, 10) * 1000;
                var pad = function (n) { return String(n).padStart(2, '0'); };
                var set = function (k, v) { var s = el.querySelector('[data-cd=' + k + ']'); if (s) s.textContent = pad(v); };
                function tick() {
                    var d = target - Date.now();
                    if (d < 0) d = 0;
                    set('days', Math.floor(d / 86400000));
                    set('hours', Math.floor((d % 86400000) / 3600000));
                    set('mins', Math.floor((d % 3600000) / 60000));
                    set('secs', Math.floor((d % 60000) / 1000));
                    if (d <= 0) location.reload();
                }
                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @elseif ($competitions->isEmpty())
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
            <p class="text-sm font-semibold">Belum ada lomba yang dipublikasikan panitia.</p>
        </div>
    @else
        <div class="mt-6 w-full min-w-0 max-w-full space-y-6" data-lomba-register data-lookup-url="{{ route('public.lomba-register.lookup') }}">
            {{-- Langkah 1: cari No Daftar --}}
            <section class="merdeka-card overflow-hidden p-6 text-center sm:p-8">
                <h2 class="text-lg font-black text-stone-900">1. Masukkan No Daftar</h2>
                <p class="mt-1 text-sm text-stone-500">Nomor 4 digit dari Form Warga, contoh: 0007.</p>

                <div class="mx-auto mt-5 w-full max-w-xs">
                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500">No Daftar</label>
                    <input type="text" inputmode="numeric" autocomplete="off" data-lookup-input value="{{ old('registration_number') }}" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 text-center text-2xl font-bold tracking-[0.4em] text-stone-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="0007">
                    <button type="button" data-lookup-btn class="mt-3 w-full rounded-xl bg-red-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-red-800">Cari Nama</button>
                    <p class="mt-2 hidden text-xs font-semibold text-red-600" data-lookup-error></p>
                </div>
            </section>

            {{-- Langkah 2: hasil + pilih lomba --}}
            <form method="POST" action="{{ route('public.lomba-register.store') }}" class="hidden space-y-6" data-register-form>
                @csrf
                <input type="hidden" name="registration_number" data-selected-number>

                <section class="merdeka-card overflow-hidden p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                        <div>
                            <h2 class="text-lg font-black text-stone-900">2. Data Peserta</h2>
                            <p class="text-sm text-stone-500">Pastikan datanya benar.</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-member-card>
                        <div class="rounded-xl bg-stone-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Nama</p>
                            <p class="mt-1 break-words text-sm font-black text-stone-900" data-field-name>—</p>
                        </div>
                        <div class="rounded-xl bg-stone-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">No Daftar</p>
                            <p class="mt-1 font-mono text-sm font-black tracking-widest text-red-700" data-field-number>—</p>
                        </div>
                        <div class="rounded-xl bg-stone-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Umur / Kategori</p>
                            <p class="mt-1 text-sm font-black text-stone-900" data-field-age>—</p>
                        </div>
                        <div class="rounded-xl bg-stone-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Gender</p>
                            <p class="mt-1 text-sm font-black text-stone-900" data-field-gender>—</p>
                        </div>
                    </div>
                    <p class="mt-3 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800" data-no-age>
                        Umur peserta belum diisi di Form Warga, jadi lomba yang ada batas umurnya belum bisa dipilih. Hubungi panitia untuk melengkapi umur.
                    </p>
                </section>

                <section class="merdeka-card overflow-hidden p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="h-7 w-1.5 rounded-full bg-red-600"></span>
                        <div>
                            <h2 class="text-lg font-black text-stone-900">3. Pilih Lomba</h2>
                            <p class="text-sm text-stone-500">Centang lomba yang ingin diikuti (boleh lebih dari satu).</p>
                        </div>
                    </div>

                    @error('competition_ids') <p class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ $message }}</p> @enderror

                    <div class="mt-5 space-y-3" data-competition-list>
                        {{-- diisi oleh JS --}}
                    </div>
                </section>

                <div class="merdeka-card p-5 sm:p-6 sm:flex sm:items-center sm:justify-between">
                    <p class="text-sm text-stone-500">Terdaftar untuk <span class="font-bold text-stone-800" data-selected-count>0</span> lomba.</p>
                    <button type="submit" class="mt-4 w-full rounded-xl bg-red-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50 sm:mt-0 sm:w-auto" data-submit-btn disabled>
                        Daftarkan ke Lomba
                    </button>
                </div>
            </form>
        </div>

        {{-- template item lomba --}}
        <template id="competition-item-template">
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-red-200 data-[disabled=true]:cursor-not-allowed data-[disabled=true]:bg-stone-50 data-[disabled=true]:opacity-60">
                <input type="checkbox" name="competition_ids[]" class="mt-0.5 h-5 w-5 shrink-0 rounded border-stone-300 text-red-600 focus:ring-red-500" data-competition-checkbox>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-stone-900" data-competition-name></span>
                    <span class="mt-0.5 block text-xs text-stone-500" data-competition-meta></span>
                </span>
                <span class="hidden shrink-0 rounded-full bg-stone-200 px-2.5 py-1 text-[11px] font-bold text-stone-600" data-competition-badge></span>
            </label>
        </template>

        <script>
            (function () {
                const root = document.querySelector('[data-lomba-register]');
                if (!root) return;

                const lookupUrl = root.dataset.lookupUrl;
                const input = root.querySelector('[data-lookup-input]');
                const lookupBtn = root.querySelector('[data-lookup-btn]');
                const lookupError = root.querySelector('[data-lookup-error]');
                const form = root.querySelector('[data-register-form]');
                const selectedNumber = root.querySelector('[data-selected-number]');
                const list = root.querySelector('[data-competition-list]');
                const template = document.getElementById('competition-item-template');
                const submitBtn = root.querySelector('[data-submit-btn]');
                const selectedCount = root.querySelector('[data-selected-count]');
                const noAge = root.querySelector('[data-no-age]');

                const showError = (msg) => {
                    lookupError.textContent = msg;
                    lookupError.classList.remove('hidden');
                    form.classList.add('hidden');
                };
                const clearError = () => {
                    lookupError.textContent = '';
                    lookupError.classList.add('hidden');
                };

                const updateCount = () => {
                    const n = list.querySelectorAll('[data-competition-checkbox]:checked').length;
                    selectedCount.textContent = String(n);
                    submitBtn.disabled = n === 0;
                };

                const renderCompetitions = (competitions) => {
                    list.innerHTML = '';
                    competitions.forEach((c) => {
                        const node = template.content.firstElementChild.cloneNode(true);
                        const checkbox = node.querySelector('[data-competition-checkbox]');
                        const name = node.querySelector('[data-competition-name]');
                        const meta = node.querySelector('[data-competition-meta]');
                        const badge = node.querySelector('[data-competition-badge]');

                        checkbox.value = c.id;
                        name.textContent = c.name;
                        meta.textContent = c.age_limit ? c.age_limit : 'Terbuka semua umur';

                        const disabled = c.already || !c.eligible;
                        if (disabled) {
                            checkbox.disabled = true;
                            checkbox.checked = false;
                            node.setAttribute('data-disabled', 'true');
                            badge.textContent = c.reason || 'Tidak tersedia';
                            badge.classList.remove('hidden');
                            if (c.already) {
                                badge.classList.remove('bg-stone-200', 'text-stone-600');
                                badge.classList.add('bg-emerald-100', 'text-emerald-700');
                            }
                        }

                        checkbox.addEventListener('change', updateCount);
                        list.appendChild(node);
                    });
                    updateCount();
                };

                const doLookup = async () => {
                    const no = (input.value || '').trim();
                    if (!no) { showError('Masukkan No Daftar terlebih dahulu.'); return; }

                    lookupBtn.disabled = true;
                    lookupBtn.textContent = 'Mencari…';
                    try {
                        const res = await fetch(`${lookupUrl}?no=${encodeURIComponent(no)}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();

                        if (!data.found) {
                            showError(data.message || 'No Daftar tidak ditemukan.');
                            return;
                        }

                        clearError();
                        selectedNumber.value = data.member.registration_number;
                        form.querySelector('[data-field-name]').textContent = data.member.name;
                        form.querySelector('[data-field-number]').textContent = data.member.registration_number;
                        form.querySelector('[data-field-age]').textContent =
                            (data.member.age !== null ? data.member.age + ' th' : 'Belum diisi') + ' · ' + data.member.category;
                        form.querySelector('[data-field-gender]').textContent = data.member.gender_label || '—';

                        noAge.classList.toggle('hidden', data.member.age !== null);

                        renderCompetitions(data.competitions);
                        form.classList.remove('hidden');
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch (e) {
                        showError('Gagal menghubungi server. Coba lagi.');
                    } finally {
                        lookupBtn.disabled = false;
                        lookupBtn.textContent = 'Cari Nama';
                    }
                };

                lookupBtn.addEventListener('click', doLookup);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); doLookup(); }
                });

                // Auto-lookup bila kembali dengan error validasi.
                if (input.value.trim()) doLookup();
            })();
        </script>
    @endif
</x-layouts.public>
