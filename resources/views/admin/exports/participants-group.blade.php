<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Tim & Peserta - {{ $competition->name }}</title>
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
        .gender-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #0f172a; }
        .gender-title:first-of-type { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { text-align: left; padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        td.num, th.num { text-align: center; white-space: nowrap; }
        .team td { background: #eef2f7; border-top: 2px solid #cbd5e1; font-weight: 700; color: #0f172a; }
        .team .meta { font-weight: 400; color: #475569; font-size: 11px; }
        .regno { display: inline-block; background: #c1121f; color: #fff; font-family: 'Consolas', monospace; font-weight: 700; letter-spacing: .06em; padding: 2px 7px; border-radius: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .juara { background: #fef3c7; color: #b45309; }
        .lolos { background: #d1fae5; color: #047857; }
        .gugur { background: #f1f5f9; color: #64748b; }
        @media print { .toolbar { display: none; } body { padding: 0; } .team td, .regno, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn" onclick="window.close()">Tutup</button>
    </div>

    <div class="head">
        <div>
            <h1>Daftar Tim & Peserta — {{ $competition->name }}</h1>
            <p class="muted" style="margin:4px 0 0">{{ $site?->site_name ?? 'Portal Warga' }}{{ $event ? ' · ' . $event->name : '' }}</p>
        </div>
        <p class="muted">Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</p>
    </div>

    <p class="summary"><strong>{{ $totalTeams }}</strong> tim · <strong>{{ $totalParticipants }}</strong> peserta · <strong>{{ $competition->total_rounds }}</strong> babak{{ $competition->age_limit_label ? ' · ' . $competition->age_limit_label : '' }}</p>

    @forelse ($teamsByGender as $groupTeams)
        @if ($teamsByGender->count() > 1)
            <p class="gender-title">Kategori {{ $groupTeams->first()->gender_category_label }} · {{ $groupTeams->count() }} tim</p>
        @endif

        @foreach ($groupTeams as $team)
            <table>
                <thead>
                    <tr class="team">
                        <td colspan="4">
                            {{ $team->display_name }}
                            <span class="meta">
                                · {{ $team->members->count() }} anggota
                                · Babak {{ $team->round }}{{ $team->round == $competition->total_rounds ? ' (Final)' : '' }}
                                ·
                                @if ($team->rank)
                                    <span class="badge juara">Juara {{ $team->rank }}</span>
                                @elseif ($team->status === 'eliminated')
                                    <span class="badge gugur">Gugur</span>
                                @else
                                    <span class="badge lolos">Lolos</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th style="width:80px">No Daftar</th>
                        <th>Nama Anggota</th>
                        <th class="num" style="width:60px">Umur</th>
                        <th>Blok / No HP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($team->members as $m)
                        <tr>
                            <td><span class="regno">{{ $m->familyMember?->registration_number ?: '—' }}</span></td>
                            <td>{{ $m->name }}</td>
                            <td class="num">{{ $m->age !== null ? $m->age . ' th' : '-' }}</td>
                            <td>{{ $m->resident_block ?: '-' }}{{ $m->phone_number ? ' · ' . $m->phone_number : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:14px">Belum ada anggota di tim ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @empty
        <p style="text-align:center;color:#94a3b8;padding:24px">Belum ada tim untuk lomba ini.</p>
    @endforelse

    @if ($unassigned->isNotEmpty())
        <p class="gender-title">Menunggu Dikelompokkan · {{ $unassigned->count() }} peserta</p>
        <table>
            <thead>
                <tr>
                    <th style="width:80px">No Daftar</th>
                    <th>Nama</th>
                    <th class="num" style="width:60px">Umur</th>
                    <th>Blok / No HP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unassigned as $p)
                    <tr>
                        <td><span class="regno">{{ $p->familyMember?->registration_number ?: '—' }}</span></td>
                        <td>{{ $p->name }}</td>
                        <td class="num">{{ $p->age !== null ? $p->age . ' th' : '-' }}</td>
                        <td>{{ $p->resident_block ?: '-' }}{{ $p->phone_number ? ' · ' . $p->phone_number : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
