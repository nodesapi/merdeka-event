<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Peserta - {{ $competition->name }}</title>
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
        td.num, th.num { text-align: center; white-space: nowrap; }
        .cat td { background: #eef2f7; border-top: 2px solid #cbd5e1; font-weight: 700; color: #0f172a; }
        .regno { display: inline-block; background: #c1121f; color: #fff; font-family: 'Consolas', monospace; font-weight: 700; letter-spacing: .06em; padding: 2px 7px; border-radius: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .juara { background: #fef3c7; color: #b45309; }
        .lolos { background: #d1fae5; color: #047857; }
        .gugur { background: #f1f5f9; color: #64748b; }
        @media print { .toolbar { display: none; } body { padding: 0; } .cat td, .regno, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div>
            <h1>Daftar Peserta — {{ $competition->name }}</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}{{ $event ? ' · ' . $event->name : '' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    <p class="summary"><strong>{{ $totalParticipants }}</strong> peserta · <strong>{{ $competition->total_rounds }}</strong> babak{{ $competition->age_limit_label ? ' · ' . $competition->age_limit_label : '' }}</p>

    <table>
        <thead>
            <tr>
                <th style="width:80px">No Daftar</th>
                <th style="width:36px" class="num">No</th>
                <th>Nama / Regu</th>
                <th class="num">Umur</th>
                <th>Babak</th>
                <th>Status</th>
                <th>Blok</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byCategory as $group)
                <tr class="cat">
                    <td colspan="7">Kategori {{ $group->first()->age_category_label }} · {{ $group->count() }} peserta</td>
                </tr>
                @foreach ($group as $p)
                    <tr>
                        <td><span class="regno">{{ $p->familyMember?->registration_number ?: '—' }}</span></td>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td>{{ $p->name }}</td>
                        <td class="num">{{ $p->age !== null ? $p->age . ' th' : '-' }}</td>
                        <td>Babak {{ $p->round }}{{ $p->round == $competition->total_rounds ? ' (Final)' : '' }}</td>
                        <td>
                            @if ($p->rank)
                                <span class="badge juara">Juara {{ $p->rank }}</span>
                            @elseif ($p->status === 'eliminated')
                                <span class="badge gugur">Gugur</span>
                            @else
                                <span class="badge lolos">Lolos</span>
                            @endif
                        </td>
                        <td>{{ $p->resident_block ?: '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:24px">Belum ada peserta untuk lomba ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
