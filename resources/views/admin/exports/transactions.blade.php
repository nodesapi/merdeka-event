<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Transaksi</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 18px; }
        .summary { display: flex; gap: 16px; margin-bottom: 18px; }
        .summary > div { flex: 1 1 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; }
        .summary .label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 5px; }
        .summary .value { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .in { color: #047857; font-weight: 700; }
        .out { color: #b91c1c; font-weight: 700; }
        tfoot td { font-weight: 700; border-top: 2px solid #cbd5e1; }
        @media print { .toolbar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div>
            <h1>Laporan Transaksi Dana</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }} · {{ ['all' => 'Semua transaksi', 'income' => 'Dana masuk', 'expense' => 'Dana keluar'][$filter] ?? 'Semua transaksi' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="summary">
        <div><div class="label">Dana Masuk</div><div class="value in">Rp{{ number_format($totalIncome, 0, ',', '.') }}</div></div>
        <div><div class="label">Dana Keluar</div><div class="value out">Rp{{ number_format($totalExpense, 0, ',', '.') }}</div></div>
        <div><div class="label">Saldo</div><div class="value">Rp{{ number_format($balance, 0, ',', '.') }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Keterangan</th>
                <th>Blok/PJ</th>
                <th>Bank/Metode</th>
                <th class="num">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->type === 'expense' ? 'Keluar' : 'Masuk' }}</td>
                    <td>{{ $t->description }}</td>
                    <td>{{ $t->resident_block ?: '-' }}</td>
                    <td>{{ $t->bank_name ?: '-' }}{{ $t->account_number ? ' · ' . $t->account_number : '' }}</td>
                    <td class="num {{ $t->type === 'expense' ? 'out' : 'in' }}">{{ $t->type === 'expense' ? '-' : '+' }}Rp{{ number_format($t->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:24px">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Saldo Akhir</td>
                <td class="num">Rp{{ number_format($balance, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
