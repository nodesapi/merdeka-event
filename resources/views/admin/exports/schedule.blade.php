<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rundown Acara</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; font-size: 12px; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 20px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155; }
        .btn-primary { background: #c1121f; border-color: #c1121f; color: #fff; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #64748b; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #c1121f; padding-bottom: 14px; margin-bottom: 22px; }
        .head-brand { display: flex; align-items: center; gap: 12px; }
        .head-brand img { height: 48px; width: auto; }
        .day-heading { margin: 22px 0 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #c1121f; }
        .day-heading:first-of-type { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        th.time, td.time { width: 140px; white-space: nowrap; }
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
                <h1>Rundown Acara</h1>
                <p class="muted" style="margin:4px 0 0">{{ $event?->name ?? $site?->site_name ?? 'Portal Warga' }}</p>
            </div>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    @php
        $groupedSchedules = $schedules->groupBy(fn ($item) => optional($item->scheduled_at)->format('Y-m-d') ?? 'tbd');
    @endphp

    @if ($schedules->isEmpty())
        <p class="muted" style="text-align:center;padding:32px 0">Belum ada susunan acara.</p>
    @else
        @foreach ($groupedSchedules as $dateKey => $daySchedules)
            <p class="day-heading">
                @if ($dateKey === 'tbd')
                    Waktu Belum Ditentukan
                @else
                    {{ \Illuminate\Support\Carbon::parse($dateKey)->locale('id')->translatedFormat('l, d F Y') }}
                @endif
            </p>
            <table>
                <thead>
                    <tr>
                        <th class="time">Waktu</th>
                        <th>Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daySchedules as $item)
                        <tr>
                            <td class="time">{{ $item->time_label }}</td>
                            <td>{{ $item->activity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
