<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pendaftaran Warga</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .verified { background: #d1fae5; color: #047857; }
        .rejected { background: #fee2e2; color: #b91c1c; }
        .pending { background: #fef3c7; color: #b45309; }
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
            <h1>Laporan Pendaftaran Warga</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}{{ $event ? ' · ' . $event->name : '' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    @php $grandTotal = $submissions->sum(fn ($s) => (float) $s->submitted_total); @endphp

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Kepala Keluarga</th>
                <th>Blok</th>
                <th>No HP</th>
                <th>Metode</th>
                <th class="num">Anggota</th>
                <th class="num">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submissions as $s)
                @php
                    $badge = match ($s->status) {
                        'verified' => 'verified',
                        'rejected' => 'rejected',
                        default => 'pending',
                    };
                @endphp
                <tr>
                    <td>{{ $s->reference_code }}</td>
                    <td>{{ $s->head_of_family_name }}</td>
                    <td>{{ $s->resident_block }}</td>
                    <td>{{ $s->phone_number }}</td>
                    <td>{{ ['transfer' => 'Transfer', 'cash' => 'Tunai'][$s->payment_method] ?? 'Lainnya' }}</td>
                    <td class="num">{{ $s->family_members_count }}</td>
                    <td class="num">Rp{{ number_format($s->submitted_total, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $badge }}">{{ strtoupper($s->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px">Belum ada pendaftaran warga.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Total Diajukan ({{ $submissions->count() }} pendaftaran)</td>
                <td class="num">Rp{{ number_format($grandTotal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
