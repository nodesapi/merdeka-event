<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bukti Pendaftaran - {{ $submission->reference_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 13px; background: #f8fafc; }
        .sheet { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; }
        .toolbar { max-width: 640px; margin: 0 auto 16px; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 9px 16px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        .btn-wa { background: #16a34a; border-color: #16a34a; color: #fff; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #c1121f; padding-bottom: 16px; margin-bottom: 18px; }
        h1 { font-size: 20px; margin: 0; color: #111827; }
        .muted { color: #64748b; }
        .row { display: flex; flex-wrap: wrap; gap: 8px 28px; margin-bottom: 18px; }
        .row .item { font-size: 13px; }
        .row .label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
        .row .val { font-weight: 700; color: #0f172a; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { text-align: left; padding: 9px 10px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        .regno { display: inline-block; background: #c1121f; color: #fff; font-family: 'Consolas', monospace; font-weight: 700; letter-spacing: .06em; padding: 3px 9px; border-radius: 5px; }
        .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; margin: 20px 0 6px; }
        .pay { margin-top: 18px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; background: #f8fafc; }
        .pay .amount { font-size: 20px; font-weight: 800; color: #0f172a; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .paid { background: #d1fae5; color: #047857; }
        .verified { background: #d1fae5; color: #047857; }
        .pending { background: #fef3c7; color: #b45309; }
        .foot { margin-top: 22px; padding-top: 14px; border-top: 1px dashed #cbd5e1; font-size: 12px; color: #64748b; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; max-width: none; }
            .regno, .badge, .head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $isPaid = $submission->payment_status === 'paid';
        $isVerified = $submission->status === 'verified';
    @endphp

    <div class="toolbar">
        @if ($submission->whatsappUrl())
            <a class="btn btn-wa" href="{{ $submission->whatsappUrl() }}" target="_blank" rel="noopener">Kirim via WhatsApp</a>
        @endif
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                <h1>Bukti Pendaftaran Warga</h1>
                <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?: 'Portal Warga' }}{{ $submission->event ? ' · ' . $submission->event->name : '' }}</p>
            </div>
            <div style="text-align:right">
                <div class="regno">{{ $submission->reference_code }}</div>
                <p class="muted" style="margin:6px 0 0; font-size:11px">Dicetak {{ $generatedAt->translatedFormat('d M Y H:i') }}</p>
            </div>
        </div>

        <div class="row">
            <div class="item">
                <div class="label">Kepala Keluarga</div>
                <div class="val">{{ $submission->head_of_family_name }}</div>
            </div>
            <div class="item">
                <div class="label">Blok</div>
                <div class="val">{{ $submission->resident_block ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">No. HP / WA</div>
                <div class="val">{{ $submission->phone_number ?: '-' }}</div>
            </div>
        </div>

        <div class="section-title">No. Daftar Anggota Keluarga</div>
        <table>
            <thead>
                <tr>
                    <th style="width:90px">No. Daftar</th>
                    <th>Nama</th>
                    <th>Hubungan</th>
                    <th>Umur</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submission->familyMembers as $index => $member)
                    <tr>
                        <td><span class="regno">{{ $member->registration_number ?: '—' }}</span></td>
                        <td style="font-weight:600">{{ $member->name }}</td>
                        <td>{{ $index === 0 ? 'Kepala Keluarga' : ucfirst($member->relationship) }}</td>
                        <td>{{ $member->age !== null ? $member->age . ' th' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pay">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap">
                <div>
                    <div class="label" style="color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.05em">Total Iuran / Kontribusi</div>
                    <div class="amount">Rp {{ number_format((float) $submission->submitted_total, 0, ',', '.') }}</div>
                </div>
                <div style="text-align:right">
                    @if ($isPaid)
                        <span class="badge paid">LUNAS via QRIS</span>
                        @if ($submission->payment_paid_at)
                            <div class="muted" style="margin-top:6px; font-size:11px">{{ $submission->payment_paid_at->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}</div>
                        @endif
                    @elseif ($isVerified)
                        <span class="badge verified">TERVERIFIKASI</span>
                    @else
                        <span class="badge pending">MENUNGGU</span>
                    @endif
                </div>
            </div>
        </div>

        <p class="foot">Simpan No. Daftar ini — dipakai untuk pendaftaran lomba & pengambilan doorprize. Dokumen ini dibuat otomatis oleh panitia.</p>
    </div>
</body>
</html>
