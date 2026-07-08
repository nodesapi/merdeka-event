<x-layouts.public title="Pembayaran QRIS" :eventName="$event?->name" :hideWelcome="true">
    @php
        $payAmount = (float) ($submission->payment_pay_amount ?? 0);
        $isPaid = $submission->payment_status === 'paid';
        $statusUrl = route('public.qris-status', $submission->reference_code);
        // Icon di tengah QR: pakai Favicon website (fallback ke Logo bila favicon kosong).
        $qrisLogo = $site?->favicon_url ?: $site?->logo_url;
        // QR modul tetap hitam (paling andal discan).
        $qrSvg = $submission->payment_qris_svg;
    @endphp

    <div class="mx-auto max-w-xl">
        {{-- Header --}}
        <div class="text-center">
            <span class="merdeka-badge">Pembayaran Iuran</span>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Bayar via QRIS</h1>
            <p class="mt-1 text-sm leading-6 text-stone-500">
                No. Registrasi <span class="font-mono font-semibold text-stone-700">{{ $submission->reference_code }}</span> · a/n {{ $submission->head_of_family_name }}
            </p>
        </div>

        {{-- Kartu QRIS --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm" data-qris-card>
            {{-- State: BELUM BAYAR --}}
            <div @if ($isPaid) class="hidden" @endif data-qris-pending>
                <div class="rounded-xl bg-red-50 p-4 text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-red-700">Nominal yang harus dibayar</p>
                    <p class="mt-1 text-3xl font-black text-red-800">Rp {{ number_format($payAmount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-red-600">Bayar <b>persis</b> sampai digit terakhir agar terverifikasi otomatis.</p>
                </div>

                <div class="mt-5 flex justify-center">
                    @if ($qrSvg)
                        {{-- Frame ber-brand + icon website di tengah QR --}}
                        <div class="inline-block overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm ring-1 ring-red-100">
                            {{-- Strip gradient atas: nempel edge-to-edge --}}
                            <div class="h-2 bg-gradient-to-r from-red-600 via-red-500 to-amber-400"></div>
                            @if ($site?->qris_logo_url)
                                <div class="flex justify-center border-b border-stone-100 px-4 pb-3 pt-4">
                                    <img src="{{ $site->qris_logo_url }}" alt="QRIS" class="h-8 object-contain">
                                </div>
                            @endif
                            <div class="p-4">
                                <div class="relative [&_svg]:block [&_svg]:h-60 [&_svg]:w-60 sm:[&_svg]:h-64 sm:[&_svg]:w-64">
                                    {!! $qrSvg !!}
                                    @if ($qrisLogo)
                                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-white p-1.5 shadow-md ring-2 ring-white sm:h-14 sm:w-14">
                                                <img src="{{ $qrisLogo }}" alt="Icon" class="max-h-full max-w-full object-contain">
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex h-64 w-64 items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-stone-50 text-center text-sm text-stone-400">
                            QR tidak tersedia. Silakan hubungi panitia.
                        </div>
                    @endif
                </div>

                @if ($submission->payment_expires_at)
                    <p class="mt-4 text-center text-xs text-stone-500">
                        Berlaku sampai <span class="font-semibold text-stone-700">{{ $submission->payment_expires_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</span>
                        <span data-qris-countdown class="ml-1 font-mono text-red-600"></span>
                    </p>
                @endif

                <div class="mt-5 rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-600">
                    <p class="font-semibold text-stone-700">Cara bayar:</p>
                    <ol class="mt-1 list-decimal space-y-0.5 pl-5">
                        <li>Buka aplikasi e-wallet / m-banking, pilih <b>Scan QRIS</b>.</li>
                        <li>Scan QR di atas, pastikan nominal sama persis.</li>
                        <li>Selesaikan pembayaran — halaman ini otomatis berubah saat lunas.</li>
                    </ol>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2 text-sm text-stone-500" data-qris-checking>
                    <svg class="h-4 w-4 animate-spin text-red-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Menunggu pembayaran…
                </div>
            </div>

            {{-- State: LUNAS --}}
            <div @unless ($isPaid) class="hidden" @endunless data-qris-paid>
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="mt-4 text-xl font-black text-stone-900">Pembayaran Lunas!</h2>
                    <p class="mt-1 text-sm text-stone-500">Terima kasih. Iuran sebesar <b>Rp {{ number_format($payAmount, 0, ',', '.') }}</b> sudah kami terima dan tercatat otomatis.</p>
                    <a href="{{ route('public.registration-receipt', $submission->reference_code) }}" target="_blank" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-red-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-800">
                        Simpan Bukti Pendaftaran (PDF)
                    </a>
                </div>
            </div>
        </div>

        {{-- Daftar No Daftar anggota (jika baru submit) --}}
        @if (! empty($registeredMembers))
            <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Simpan No Daftar Anggota</p>
                <div class="mt-3 divide-y divide-stone-100">
                    @foreach ($registeredMembers as $m)
                        <div class="flex items-center justify-between gap-3 py-2">
                            <span class="truncate text-sm text-stone-700">{{ $m['name'] }} <span class="text-xs text-stone-400">({{ ucfirst($m['relationship']) }})</span></span>
                            <span class="shrink-0 rounded-md bg-red-50 px-2.5 py-1 font-mono text-sm font-bold text-red-700">{{ $m['registration_number'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-6 text-center">
            <a href="{{ route('public.family-form') }}" class="text-sm font-semibold text-stone-500 hover:text-red-700">&larr; Kembali ke Form Warga</a>
        </div>
    </div>

    @unless ($isPaid)
        <script>
            (function () {
                const statusUrl = @json($statusUrl);
                const card = document.querySelector('[data-qris-card]');
                if (!card) return;
                const pending = card.querySelector('[data-qris-pending]');
                const paid = card.querySelector('[data-qris-paid]');
                let stopped = false;

                async function poll() {
                    if (stopped) return;
                    try {
                        const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.paid) {
                                stopped = true;
                                if (pending) pending.classList.add('hidden');
                                if (paid) paid.classList.remove('hidden');
                                return;
                            }
                        }
                    } catch (e) { /* abaikan, coba lagi */ }
                    setTimeout(poll, 4000);
                }
                setTimeout(poll, 4000);

                // Hitung mundur masa berlaku (opsional).
                const expiresAt = @json(optional($submission->payment_expires_at)->toIso8601String());
                const cd = card.querySelector('[data-qris-countdown]');
                if (expiresAt && cd) {
                    const target = new Date(expiresAt).getTime();
                    const tick = () => {
                        const diff = target - Date.now();
                        if (diff <= 0) { cd.textContent = '(kedaluwarsa)'; return; }
                        const m = Math.floor(diff / 60000);
                        const s = Math.floor((diff % 60000) / 1000);
                        cd.textContent = '(' + m + 'm ' + String(s).padStart(2, '0') + 's)';
                        setTimeout(tick, 1000);
                    };
                    tick();
                }
            })();
        </script>
    @endunless
</x-layouts.public>
