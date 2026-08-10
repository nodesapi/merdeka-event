<?php

namespace App\Support;

/**
 * Pengelompokan umur untuk keadilan (fairness) lomba.
 * Bracket: Balita (1-4), Anak (5-8), Remaja (9-15), Dewasa (16+).
 */
class AgeCategory
{
    /**
     * Daftar bracket: [key, label, min, max]. max null = tak terbatas (Dewasa).
     *
     * @return array<int, array{key:string,label:string,min:int,max:?int}>
     */
    public static function brackets(): array
    {
        return [
            ['key' => 'balita', 'label' => 'Balita (1–4 tahun)', 'min' => 0, 'max' => 4],
            ['key' => 'anak', 'label' => 'Anak (5–8 tahun)', 'min' => 5, 'max' => 8],
            ['key' => 'remaja', 'label' => 'Remaja (9–15 tahun)', 'min' => 9, 'max' => 15],
            ['key' => 'dewasa', 'label' => 'Dewasa (16 tahun ke atas)', 'min' => 16, 'max' => null],
        ];
    }

    /**
     * Kembalikan key kategori untuk sebuah umur. Null jika umur tak diketahui.
     */
    public static function keyFor(?int $age): ?string
    {
        if ($age === null) {
            return null;
        }

        foreach (self::brackets() as $bracket) {
            if ($age >= $bracket['min'] && ($bracket['max'] === null || $age <= $bracket['max'])) {
                return $bracket['key'];
            }
        }

        return 'dewasa';
    }

    /**
     * Kembalikan label kategori untuk sebuah umur.
     */
    public static function labelFor(?int $age): string
    {
        if ($age === null) {
            return 'Tanpa kategori umur';
        }

        foreach (self::brackets() as $bracket) {
            if ($bracket['key'] === self::keyFor($age)) {
                return $bracket['label'];
            }
        }

        return 'Dewasa (16 tahun ke atas)';
    }

    /**
     * Urutan sort untuk key kategori (dipakai mengurutkan grup di dashboard).
     */
    public static function order(?string $key): int
    {
        foreach (array_values(self::brackets()) as $i => $bracket) {
            if ($bracket['key'] === $key) {
                return $i;
            }
        }

        return 99;
    }
}
