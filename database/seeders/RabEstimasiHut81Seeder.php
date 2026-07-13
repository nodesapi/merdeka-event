<?php

namespace Database\Seeders;

use App\Models\RabItem;
use Illuminate\Database\Seeder;

/**
 * Seed RAB (Rencana Anggaran Biaya) sesuai data "ESTIMASI RAB ACARA 17 AGUSTUS KE-81THN WIDARI VILLAGE".
 * Item konsumsi & atribut peserta (gelang, headband, snack, makan malam) pakai qty 550 (jumlah peserta),
 * bukan 200 (jumlah target rumah) — target rumah tidak sama dengan jumlah peserta karena 1 rumah bisa
 * mengirim lebih dari 1 orang.
 * Tidak menyentuh 3 item yang sudah dibuat manual oleh panitia di kategori "Hadiah Lomba Anak & Remaja"
 * (Lomba Kelereng, Lomba Makan Kerupuk, Pindah Bendera) — jadi aman dijalankan tanpa menimpa data yang ada.
 * Jalankan terpisah dari DatabaseSeeder: php artisan db:seed --class=RabEstimasiHut81Seeder
 */
class RabEstimasiHut81Seeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Hadiah Lomba Anak & Remaja (Lomba Kelereng, Lomba Makan Kerupuk, Pindah Bendera sudah diinput manual)
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Sangkut Topi', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 60000, 'jumlah_rencana' => 60000, 'catatan' => 'Juara 1, 2, & 3'],
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Hias Sepeda', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 50000, 'jumlah_rencana' => 50000, 'catatan' => 'Juara 1'],
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Mewarnai', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 60000, 'jumlah_rencana' => 60000, 'catatan' => 'Juara 1, 2, & 3'],
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Lempar Bola ke Gelas', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 50000, 'jumlah_rencana' => 50000, 'catatan' => 'Dibuat beberapa kali sampai batas maks Rp50.000'],
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Tarik Tambang', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 400000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],
            ['kategori' => 'Hadiah Lomba Anak & Remaja', 'nama_item' => 'Bola', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 400000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],

            // Hadiah Lomba Dewasa
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Balap Karung', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 60000, 'jumlah_rencana' => 120000, 'catatan' => 'Juara 1, 2, & 3'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Sangkut Topi', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 60000, 'jumlah_rencana' => 120000, 'catatan' => 'Juara 1, 2, & 3'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Lomba Kelereng', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 60000, 'jumlah_rencana' => 120000, 'catatan' => 'Juara 1, 2, & 3'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Pindahkan Tepung', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 200000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Bola Sarung', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 200000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Estafet Sarung', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 400000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],
            ['kategori' => 'Hadiah Lomba Dewasa', 'nama_item' => 'Tarik Tambang', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 200000, 'jumlah_rencana' => 400000, 'catatan' => 'Juara 1, 2, & 3 (grup maks 5-7 orang)'],

            // Perlengkapan
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Banner 17an', 'volume' => 1, 'satuan' => 'pcs', 'harga_satuan' => 200000, 'jumlah_rencana' => 200000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Bendera Segitiga Merah Putih 6m', 'volume' => 10, 'satuan' => 'pcs', 'harga_satuan' => 10000, 'jumlah_rencana' => 100000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Gelang Peserta', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 2500, 'jumlah_rencana' => 1375000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Headband', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 2500, 'jumlah_rencana' => 1375000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Kelereng', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 10000, 'jumlah_rencana' => 10000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Tali Tambang Goni 16mm', 'volume' => 15, 'satuan' => 'meter', 'harga_satuan' => 17000, 'jumlah_rencana' => 255000, 'catatan' => 'Harga per meter'],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Kerupuk', 'volume' => 5, 'satuan' => 'bungkus', 'harga_satuan' => 10000, 'jumlah_rencana' => 50000, 'catatan' => 'Harga per bungkus'],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Karung Goni', 'volume' => 5, 'satuan' => 'pcs', 'harga_satuan' => 20000, 'jumlah_rencana' => 100000, 'catatan' => null],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Peralatan Makan', 'volume' => 6, 'satuan' => 'pack', 'harga_satuan' => 25000, 'jumlah_rencana' => 150000, 'catatan' => 'Per pack isi 50 (sendok, piring plastik, dll)'],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Peralatan Minum', 'volume' => 6, 'satuan' => 'pack', 'harga_satuan' => 15000, 'jumlah_rencana' => 90000, 'catatan' => 'Per pack isi 50 (gelas plastik)'],
            ['kategori' => 'Perlengkapan', 'nama_item' => 'Dekorasi Lainnya', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 500000, 'jumlah_rencana' => 500000, 'catatan' => null],

            // Konsumsi
            ['kategori' => 'Konsumsi', 'nama_item' => 'Snack Pagi Tgl 16', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 8000, 'jumlah_rencana' => 4400000, 'catatan' => 'Donat, puding & air mineral'],
            ['kategori' => 'Konsumsi', 'nama_item' => 'Snack Sore Tgl 16', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 8000, 'jumlah_rencana' => 4400000, 'catatan' => 'Infused water, mie goreng & telur ceplok'],
            ['kategori' => 'Konsumsi', 'nama_item' => 'Snack Pagi Tgl 17', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 8000, 'jumlah_rencana' => 4400000, 'catatan' => 'Kue subuh & air mineral'],
            ['kategori' => 'Konsumsi', 'nama_item' => 'Snack Sore Tgl 17', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 8000, 'jumlah_rencana' => 4400000, 'catatan' => 'Biskuit, wafer & susu UHT'],
            ['kategori' => 'Konsumsi', 'nama_item' => 'Makan Malam Acara Puncak', 'volume' => 550, 'satuan' => 'pcs', 'harga_satuan' => 35000, 'jumlah_rencana' => 19250000, 'catatan' => 'Tumpeng'],

            // Doorprize
            ['kategori' => 'Doorprize', 'nama_item' => 'Doorprize', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 1000000, 'jumlah_rencana' => 1000000, 'catatan' => 'Voucher Alfamart, dll'],
        ];

        foreach ($items as $item) {
            RabItem::updateOrCreate(
                ['kategori' => $item['kategori'], 'nama_item' => $item['nama_item']],
                array_merge($item, [
                    'realisasi' => 0,
                    'pj' => null,
                    'status' => 'belum',
                ])
            );
        }
    }
}
