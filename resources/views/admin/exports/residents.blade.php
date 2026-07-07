<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Data Warga</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 14px; }
        .summary { margin: 0 0 18px; color: #334155; font-size: 12px; }
        .summary strong { color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .group td { background: #eef2f7; border-top: 2px solid #cbd5e1; font-weight: 700; color: #0f172a; }
        .regno { display: inline-block; background: #c1121f; color: #fff; font-family: 'Consolas', monospace; font-weight: 700; letter-spacing: .06em; padding: 2px 7px; border-radius: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .verified { background: #d1fae5; color: #047857; }
        .rejected { background: #fee2e2; color: #b91c1c; }
        .pending { background: #fef3c7; color: #b45309; }
        .lomba-yes { color: #047857; font-weight: 600; }
        .lomba-no { color: #94a3b8; }
        .chip-kepala { display: inline-block; background: #e2e8f0; color: #475569; font-size: 9px; font-weight: 700; text-transform: uppercase; padding: 1px 5px; border-radius: 4px; margin-left: 4px; }
        @media print { .toolbar { display: none; } body { padding: 0; } .group td { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .regno { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div>
            <h1>Laporan Data Warga</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}{{ $event ? ' · ' . $event->name : '' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    @php
        $totalHouseholds = $submissions->count();
        $totalMembers = $submissions->sum(fn ($s) => $s->familyMembers->count());
        $totalChildren = $submissions->sum(fn ($s) => $s->familyMembers->where('relationship', 'anak')->count());
    @endphp

    <p class="summary"><strong>{{ $totalHouseholds }}</strong> keluarga · <strong>{{ $totalMembers }}</strong> anggota · <strong>{{ $totalChildren }}</strong> anak</p>

    <table>
        <thead>
            <tr>
                <th style="width:90px">No Daftar</th>
                <th>Nama</th>
                <th>Hubungan</th>
                <th class="num">Umur</th>
                <th>L/P</th>
                <th>Lomba</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submissions as $submission)
                @php
                    $badge = match ($submission->status) {
                        'verified' => 'verified',
                        'rejected' => 'rejected',
                        default => 'pending',
                    };
                    $statusLabel = match ($submission->status) {
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu',
                    };
                @endphp
                <tr class="group">
                    <td colspan="6">
                        Keluarga {{ $submission->head_of_family_name }} — Blok {{ $submission->resident_block ?: '-' }} · {{ $submission->reference_code }} · {{ $submission->familyMembers->count() }} anggota
                        <span class="badge {{ $badge }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
                @foreach ($submission->familyMembers->values() as $index => $member)
                    <tr>
                        <td><span class="regno">{{ $member->registration_number ?: '—' }}</span></td>
                        <td>{{ $member->name }}@if ($index === 0)<span class="chip-kepala">Kepala</span>@endif</td>
                        <td>{{ $index === 0 ? 'Kepala Keluarga' : ucfirst($member->relationship) }}</td>
                        <td class="num">{{ $member->age !== null ? $member->age . ' th' : '-' }}</td>
                        <td>{{ $member->gender ?: '-' }}</td>
                        <td>
                            @if ($member->competition_participations_count > 0)
                                <span class="lomba-yes">Ikut {{ $member->competition_participations_count }} lomba</span>
                            @else
                                <span class="lomba-no">Belum ikut</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:24px">Belum ada warga terdaftar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
