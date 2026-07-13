<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RAB (Rencana Anggaran Biaya)</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 18px; }
        .head-brand { display: flex; align-items: center; gap: 12px; }
        .head-brand img { height: 48px; width: auto; }
        .summary { display: flex; gap: 16px; margin-bottom: 18px; }
        .summary > div { flex: 1 1 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; }
        .summary .label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 5px; }
        .summary .value { font-size: 18px; font-weight: 700; }
        .category-heading { margin: 20px 0 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #c1121f; }
        .category-heading:first-of-type { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .in { color: #047857; font-weight: 700; }
        .out { color: #b91c1c; font-weight: 700; }
        .grand-total table { margin-top: 12px; }
        .grand-total td { font-weight: 700; border-top: 2px solid #cbd5e1; }
        @media print { .toolbar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div class="head-brand">
            @if ($site?->logo_url)
                <img src="{{ $site->logo_url }}" alt="Logo">
            @endif
            <div>
                <h1>Rencana Anggaran Biaya (RAB)</h1>
                <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}</p>
            </div>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="summary">
        <div><div class="label">Total Rencana</div><div class="value">Rp{{ number_format($totalRencana, 0, ',', '.') }}</div></div>
        <div><div class="label">Total Realisasi</div><div class="value">Rp{{ number_format($totalRealisasi, 0, ',', '.') }}</div></div>
        <div><div class="label">Selisih</div><div class="value {{ $totalSelisih >= 0 ? 'in' : 'out' }}">Rp{{ number_format(abs($totalSelisih), 0, ',', '.') }}</div></div>
    </div>

    @if ($items->isEmpty())
        <p class="muted" style="text-align:center;padding:32px 0">Belum ada item RAB.</p>
    @else
        @php
            $groupedItems = $items->groupBy('kategori');
        @endphp
        @foreach ($groupedItems as $kategori => $groupItems)
            <p class="category-heading">{{ $kategori }}</p>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Vol × Satuan</th>
                        <th class="num">Harga Satuan</th>
                        <th class="num">Rencana</th>
                        <th class="num">Realisasi</th>
                        <th class="num">Selisih</th>
                        <th>PJ</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupItems as $item)
                        <tr>
                            <td>{{ $item->nama_item }}</td>
                            <td>{{ (float) $item->volume }}{{ $item->satuan ? ' ' . $item->satuan : '' }}</td>
                            <td class="num">Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                            <td class="num">Rp{{ number_format($item->jumlah_rencana, 0, ',', '.') }}</td>
                            <td class="num">Rp{{ number_format($item->realisasi, 0, ',', '.') }}</td>
                            <td class="num {{ $item->selisih >= 0 ? 'in' : 'out' }}">Rp{{ number_format(abs($item->selisih), 0, ',', '.') }}</td>
                            <td>{{ $item->pj ?: '-' }}</td>
                            <td>{{ ['belum' => 'Belum', 'proses' => 'Proses', 'selesai' => 'Selesai'][$item->status] ?? $item->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        <div class="grand-total">
            <table>
                <tbody>
                    <tr>
                        <td colspan="3">Total Keseluruhan</td>
                        <td class="num">Rp{{ number_format($totalRencana, 0, ',', '.') }}</td>
                        <td class="num">Rp{{ number_format($totalRealisasi, 0, ',', '.') }}</td>
                        <td class="num {{ $totalSelisih >= 0 ? 'in' : 'out' }}">Rp{{ number_format(abs($totalSelisih), 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
