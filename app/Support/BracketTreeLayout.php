<?php

namespace App\Support;

use App\Models\CompetitionMatch;
use Illuminate\Support\Collection;

/**
 * Hitung posisi grid (kolom/baris) presisi untuk satu sisi bagan turnamen mode
 * "vs" (kiri ATAU kanan), supaya garis penghubung antar babak benar-benar nyambung
 * di titik tengah pasangan sumbernya — bukan cuma garis mengambang.
 *
 * Prinsip: setiap match di babak r (dihitung dari babak dasar sisi ini = 1) diberi
 * "rowSpan" = 2^r dan "rowStart" = index*2^r + 1 dalam grid setinggi (jumlah heat
 * babak 1 sisi ini × 2) baris. Secara matematis, titik tengah gabungan 2 match anak
 * di babak r selalu persis sama dengan titik tengah 1 match induk di babak r+1 —
 * jadi tidak perlu tahu tinggi kotak sebenarnya (px), garis selalu presisi.
 */
class BracketTreeLayout
{
    /**
     * @param  Collection<int, Collection<int, CompetitionMatch>>  $roundsForSide  round => heats (sudah di-slice ke sisi ini, urut heat_number), dari babak paling awal ke paling akhir sisi ini
     * @param  bool  $reverseColumns  true untuk sisi kanan (babak awal digambar di kolom terluar/terakhir)
     * @return array{items: array<int, array{match: CompetitionMatch, col: int, rowStart: int, rowSpan: int}>, connectors: array<int, array{col: int, rowStart: int, rowSpan: int}>, rows: int, cols: int}
     */
    public static function build(Collection $roundsForSide, bool $reverseColumns = false): array
    {
        $rounds = $roundsForSide->keys()->sort()->values();

        if ($rounds->isEmpty()) {
            return ['items' => [], 'connectors' => [], 'rows' => 0, 'cols' => 0];
        }

        $baseRound = $rounds->first();
        $round1Count = $roundsForSide->get($baseRound)->count();
        $totalRows = $round1Count * 2;
        $totalCols = $rounds->count();

        $items = [];
        $connectors = [];

        foreach ($rounds as $position => $round) {
            $depth = $round - $baseRound + 1; // 1 = babak pertama sisi ini
            $span = 2 ** $depth;
            $col = $reverseColumns ? $totalCols - $position : $position + 1;

            $heats = $roundsForSide->get($round)->values();

            foreach ($heats as $i => $match) {
                $items[] = [
                    'match' => $match,
                    'col' => $col,
                    'rowStart' => $i * $span + 1,
                    'rowSpan' => $span,
                ];
            }

            // Konektor menuju babak berikutnya SISI INI (kalau masih ada babak setelahnya).
            if ($round !== $rounds->last()) {
                $nextCol = $reverseColumns ? $col - 1 : $col + 1;
                $pairCount = intdiv($heats->count(), 2);

                for ($k = 0; $k < $pairCount; $k++) {
                    $connectors[] = [
                        'col' => $reverseColumns ? $nextCol : $col,
                        'rowStart' => $k * 2 * $span + 1,
                        'rowSpan' => 2 * $span,
                        'flip' => $reverseColumns,
                    ];
                }
            }
        }

        return ['items' => $items, 'connectors' => $connectors, 'rows' => $totalRows, 'cols' => $totalCols];
    }
}
