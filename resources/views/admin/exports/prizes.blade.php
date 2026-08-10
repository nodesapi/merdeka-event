<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Kebutuhan Hadiah</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 14px; }
        .summary { display: flex; gap: 24px; margin: 0 0 18px; }
        .summary div { color: #334155; }
        .summary strong { display: block; font-size: 18px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        th, td { text-align: left; padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: center; white-space: nowrap; }
        .lomba-title { font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 6px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; background: #eef2f7; color: #475569; }
        .empty { color: #94a3b8; font-size: 12px; margin: 0 0 22px; }
        @media print { .toolbar { display: none; } body { padding: 0; } th, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div>
            <h1>Rekap Kebutuhan Hadiah</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}{{ $event ? ' · ' . $event->name : '' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="summary">
        <div>Total Lomba<strong>{{ $rows->count() }}</strong></div>
        <div>Total Kategori / Set Juara<strong>{{ $totalCategories }}</strong></div>
        <div>Perkiraan Total Hadiah (&times;3)<strong>{{ $totalCategories * 3 }}</strong></div>
    </div>

    @foreach ($rows as $row)
        @php $competition = $row['competition']; @endphp
        <p class="lomba-title">{{ $competition->name }} <span class="badge">{{ $competition->isGroup() ? 'Grup' : 'Individu' }}</span></p>

        @if ($row['categories']->isEmpty())
            <p class="empty">Belum ada peserta{{ $competition->isGroup() ? '/tim' : '' }} terdaftar.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="num">{{ $competition->isGroup() ? 'Jumlah Tim' : 'Jumlah Peserta' }}</th>
                        <th class="num">Estimasi Hadiah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($row['categories'] as $category)
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            <td class="num">{{ $category['count'] }}</td>
                            <td class="num">3</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
