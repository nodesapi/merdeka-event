<?php

namespace App\Support;

/**
 * Pengelompokan umur untuk keadilan (fairness) lomba.
 * Bracket: 1-3, 4-6, 7-9, 10-12, 13-15, 16-19, 20+.
 */
class AgeCategory
{
    /**
     * Daftar bracket: [key, label, min, max]. max null = tak terbatas (20+).
     *
     * @return array<int, array{key:string,label:string,min:int,max:?int}>
     */
    public static function brackets(): array
    {
        return [
            ['key' => '1-3', 'label' => '1–3 tahun', 'min' => 0, 'max' => 3],
            ['key' => '4-6', 'label' => '4–6 tahun', 'min' => 4, 'max' => 6],
            ['key' => '7-9', 'label' => '7–9 tahun', 'min' => 7, 'max' => 9],
            ['key' => '10-12', 'label' => '10–12 tahun', 'min' => 10, 'max' => 12],
            ['key' => '13-15', 'label' => '13–15 tahun', 'min' => 13, 'max' => 15],
            ['key' => '16-19', 'label' => '16–19 tahun', 'min' => 16, 'max' => 19],
            ['key' => '20+', 'label' => '20 tahun ke atas', 'min' => 20, 'max' => null],
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

        return '20+';
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

        return '20 tahun ke atas';
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
