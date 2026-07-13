<?php

namespace Database\Seeders;

use App\Models\CommitteeMember;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Event;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\RabItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $committeeRole = Role::firstOrCreate(['name' => 'panitia', 'guard_name' => 'web']);
        $residentRole = Role::firstOrCreate(['name' => 'warga', 'guard_name' => 'web']);

        // Tahun acara: pakai tahun berjalan bila 17 Agustus belum lewat, jika sudah pakai tahun depan.
        $eventYear = now()->month > 8 ? now()->year + 1 : now()->year;

        $event = Event::updateOrCreate(
            ['slug' => 'merdeka-rt-07-2026'],
            [
                'name' => 'Pesta Rakyat Kemerdekaan RT 07',
                'logo' => null,
                'banner' => null,
                'location' => 'Lapangan RT 07, Komplek Merdeka Indah',
                'maps_url' => 'https://maps.app.goo.gl/contoh',
                // Contoh acara 2 hari: 16–17 Agustus. Pakai tahun ini bila 17 Agustus belum lewat, jika sudah pakai tahun depan.
                'start_date' => $eventYear . '-08-16 07:00:00',
                'end_date' => $eventYear . '-08-17 22:00:00',
                'status' => 'active',
                'recommended_contribution_amount' => 50000,
                'contribution_guidance' => 'Iuran rekomendasi per keluarga Rp50.000. Warga boleh menambahkan kontribusi sukarela, donasi, atau sponsor untuk mendukung hadiah lomba dan kebutuhan acara.',
                'description' => 'Agenda bersama warga untuk lomba 17-an, malam puncak, dan laporan dana iuran serta sumbangan secara terbuka.',
            ]
        );

        // Hanya membuat baris default bila belum ada — tidak menimpa setelan yang
        // sudah diubah admin lewat halaman Pengaturan Website.
        SiteSetting::firstOrCreate([], [
            'site_name' => 'Pesta Rakyat Kemerdekaan RT 07',
            'tagline' => 'Portal Transparansi Warga',
            'contact_whatsapp' => '081200000001',
            'contact_person' => 'Budi Santoso (Ketua Panitia)',
            'bank_name' => 'BCA',
            'bank_account_number' => '8800012345',
            'bank_account_holder' => 'Panitia 17-an RT 07',
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'ketua.panitia@merdeka.test'],
            [
                'name' => 'Budi Santoso',
                'username' => 'superadmin',
                'password' => 'password',
                'resident_block' => 'A/01',
                'phone_number' => '081200000001',
            ]
        );
        $admin->syncRoles([$adminRole]);

        $committeeMembers = [
            [
                'name' => 'Siti Lestari',
                'email' => 'sekretaris.panitia@merdeka.test',
                'username' => 'sekretaris',
                'resident_block' => 'A/02',
                'phone_number' => '081200000002',
            ],
            [
                'name' => 'Andi Saputra',
                'email' => 'bendahara.panitia@merdeka.test',
                'username' => 'bendahara',
                'resident_block' => 'B/01',
                'phone_number' => '081200000003',
            ],
            [
                'name' => 'Rina Wulandari',
                'email' => 'acara.panitia@merdeka.test',
                'username' => 'acara',
                'resident_block' => 'B/05',
                'phone_number' => '081200000004',
            ],
        ];

        foreach ($committeeMembers as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'username' => $member['username'],
                    'password' => 'password',
                    'resident_block' => $member['resident_block'],
                    'phone_number' => $member['phone_number'],
                ]
            );

            $user->syncRoles([$committeeRole]);
        }

        $residents = [
            [
                'name' => 'Dewi Puspita',
                'email' => 'dewi.warga@merdeka.test',
                'resident_block' => 'C/03',
                'phone_number' => '081300000001',
            ],
            [
                'name' => 'Rahmat Hidayat',
                'email' => 'rahmat.warga@merdeka.test',
                'resident_block' => 'D/07',
                'phone_number' => '081300000002',
            ],
            [
                'name' => 'Nina Kartika',
                'email' => 'nina.warga@merdeka.test',
                'resident_block' => 'E/02',
                'phone_number' => '081300000003',
            ],
        ];

        $residentUsers = [];

        foreach ($residents as $resident) {
            $user = User::updateOrCreate(
                ['email' => $resident['email']],
                [
                    'name' => $resident['name'],
                    'password' => 'password',
                    'resident_block' => $resident['resident_block'],
                    'phone_number' => $resident['phone_number'],
                ]
            );

            $user->syncRoles([$residentRole]);
            $residentUsers[$resident['email']] = $user;
        }

        $committeePositions = [
            ['name' => 'Budi Santoso', 'position' => 'Ketua Panitia', 'level' => 1, 'resident_block' => 'A/01', 'phone_number' => '081200000001', 'sort_order' => 1],
            ['name' => 'Hendra Gunawan', 'position' => 'Wakil Ketua', 'level' => 1, 'resident_block' => 'A/05', 'phone_number' => '081200000007', 'sort_order' => 2],
            ['name' => 'Siti Lestari', 'position' => 'Sekretaris', 'level' => 2, 'resident_block' => 'A/02', 'phone_number' => '081200000002', 'sort_order' => 1],
            ['name' => 'Andi Saputra', 'position' => 'Bendahara', 'level' => 2, 'resident_block' => 'B/01', 'phone_number' => '081200000003', 'sort_order' => 2],
            ['name' => 'Rina Wulandari', 'position' => 'Koordinator Seksi Acara', 'level' => 3, 'resident_block' => 'B/05', 'phone_number' => '081200000004', 'sort_order' => 1],
            ['name' => 'Joko Prasetyo', 'position' => 'Seksi Perlengkapan', 'level' => 3, 'resident_block' => 'C/08', 'phone_number' => '081200000005', 'sort_order' => 2],
            ['name' => 'Maya Anggraini', 'position' => 'Seksi Konsumsi', 'level' => 3, 'resident_block' => 'D/03', 'phone_number' => '081200000006', 'sort_order' => 3],
            ['name' => 'Rudi Hartono', 'position' => 'Seksi Dokumentasi', 'level' => 3, 'resident_block' => 'C/02', 'phone_number' => '081200000008', 'sort_order' => 4],
            ['name' => 'Agus Salim', 'position' => 'Seksi Keamanan', 'level' => 3, 'resident_block' => 'E/04', 'phone_number' => '081200000009', 'sort_order' => 5],
        ];

        foreach ($committeePositions as $member) {
            CommitteeMember::updateOrCreate(
                ['event_id' => $event->id, 'name' => $member['name'], 'position' => $member['position']],
                array_merge($member, ['event_id' => $event->id, 'is_active' => true])
            );
        }

        $competitions = [
            [
                'name' => 'Lomba Makan Kerupuk',
                'target_participants' => 'Anak-anak dan Remaja',
                'total_rounds' => 2,
                'description' => 'Lomba cepat makan kerupuk untuk memeriahkan pembukaan acara pagi.',
                'status' => 'published',
                'participants' => [
                    ['name' => 'Rizky Ramadhan', 'resident_block' => 'C/03', 'round' => 2, 'status' => 'active', 'rank' => 1],
                    ['name' => 'Putri Amelia', 'resident_block' => 'D/07', 'round' => 2, 'status' => 'active', 'rank' => 2],
                    ['name' => 'Fajar Nugroho', 'resident_block' => 'E/02', 'round' => 2, 'status' => 'active', 'rank' => 3],
                    ['name' => 'Dimas Prakoso', 'resident_block' => 'B/05', 'round' => 1, 'status' => 'eliminated', 'rank' => null],
                    ['name' => 'Sari Indah', 'resident_block' => 'A/09', 'round' => 1, 'status' => 'eliminated', 'rank' => null],
                ],
            ],
            [
                'name' => 'Balap Karung',
                'target_participants' => 'Anak-anak, Remaja, dan Dewasa',
                'total_rounds' => 3,
                'description' => 'Kategori putra dan putri dengan sistem heat dan final.',
                'status' => 'published',
                'participants' => [
                    ['name' => 'Bagas Wicaksono', 'resident_block' => 'C/03', 'round' => 3, 'status' => 'active', 'rank' => null],
                    ['name' => 'Nadia Safitri', 'resident_block' => 'D/07', 'round' => 3, 'status' => 'active', 'rank' => null],
                    ['name' => 'Eko Purnomo', 'resident_block' => 'B/01', 'round' => 2, 'status' => 'eliminated', 'rank' => null],
                    ['name' => 'Lia Marlina', 'resident_block' => 'E/02', 'round' => 1, 'status' => 'eliminated', 'rank' => null],
                ],
            ],
            [
                'name' => 'Tarik Tambang',
                'target_participants' => 'Perwakilan Tiap Blok',
                'total_rounds' => 3,
                'description' => 'Pertandingan beregu antar blok untuk penutupan sore hari.',
                'status' => 'published',
                'participants' => [
                    ['name' => 'Regu Blok A', 'resident_block' => 'Blok A', 'round' => 2, 'status' => 'active', 'rank' => null],
                    ['name' => 'Regu Blok B', 'resident_block' => 'Blok B', 'round' => 2, 'status' => 'active', 'rank' => null],
                    ['name' => 'Regu Blok C', 'resident_block' => 'Blok C', 'round' => 1, 'status' => 'eliminated', 'rank' => null],
                    ['name' => 'Regu Blok D', 'resident_block' => 'Blok D', 'round' => 1, 'status' => 'eliminated', 'rank' => null],
                ],
            ],
            [
                'name' => 'Lomba Hias Tumpeng',
                'target_participants' => 'Ibu-ibu PKK dan Keluarga',
                'total_rounds' => 1,
                'description' => 'Penilaian kreativitas dan kekompakan antar keluarga warga.',
                'status' => 'published',
                'participants' => [
                    ['name' => 'Keluarga Ibu Dewi', 'resident_block' => 'C/03', 'round' => 1, 'status' => 'active', 'rank' => 1],
                    ['name' => 'Keluarga Ibu Nina', 'resident_block' => 'E/02', 'round' => 1, 'status' => 'active', 'rank' => 2],
                    ['name' => 'Keluarga Pak Rahmat', 'resident_block' => 'D/07', 'round' => 1, 'status' => 'active', 'rank' => 3],
                ],
            ],
        ];

        foreach ($competitions as $competition) {
            $participants = $competition['participants'];
            unset($competition['participants']);

            $competitionModel = Competition::updateOrCreate(
                ['slug' => Str::slug($competition['name'])],
                array_merge($competition, ['event_id' => $event->id])
            );

            foreach ($participants as $participant) {
                CompetitionParticipant::updateOrCreate(
                    ['competition_id' => $competitionModel->id, 'name' => $participant['name']],
                    array_merge($participant, ['competition_id' => $competitionModel->id])
                );
            }
        }

        $transactions = [
            [
                'user' => $residentUsers['dewi.warga@merdeka.test'],
                'amount' => 150000,
                'type' => 'income',
                'bank_name' => 'BCA',
                'account_number' => '8800012345',
                'resident_block' => 'C/03',
                'description' => 'Iuran warga keluarga Ibu Dewi',
                'status' => 'approved',
            ],
            [
                'user' => $residentUsers['rahmat.warga@merdeka.test'],
                'amount' => 200000,
                'type' => 'income',
                'bank_name' => 'BRI',
                'account_number' => '7700098765',
                'resident_block' => 'D/07',
                'description' => 'Sumbangan sukarela keluarga Pak Rahmat',
                'status' => 'approved',
            ],
            [
                'user' => $residentUsers['nina.warga@merdeka.test'],
                'amount' => 125000,
                'type' => 'income',
                'bank_name' => 'Mandiri',
                'account_number' => '9900011122',
                'resident_block' => 'E/02',
                'description' => 'Iuran warga keluarga Ibu Nina',
                'status' => 'approved',
            ],
            [
                'user' => $admin,
                'amount' => 175000,
                'type' => 'expense',
                'bank_name' => 'Cash',
                'account_number' => 'Bukti-001',
                'resident_block' => 'A/01',
                'description' => 'Pembelian hadiah lomba anak-anak',
                'status' => 'approved',
            ],
            [
                'user' => $admin,
                'amount' => 95000,
                'type' => 'expense',
                'bank_name' => 'Cash',
                'account_number' => 'Bukti-002',
                'resident_block' => 'A/01',
                'description' => 'Sewa sound system dan perlengkapan acara',
                'status' => 'approved',
            ],
        ];

        foreach ($transactions as $transaction) {
            Transaction::updateOrCreate(
                [
                    'type' => $transaction['type'],
                    'description' => $transaction['description'],
                ],
                [
                    'user_id' => $transaction['user']->id,
                    'amount' => $transaction['amount'],
                    'bank_name' => $transaction['bank_name'],
                    'account_number' => $transaction['account_number'],
                    'resident_block' => $transaction['resident_block'],
                    'status' => $transaction['status'],
                ]
            );
        }

        $rabItems = [
            ['kategori' => 'Konsumsi', 'nama_item' => 'Nasi kotak panitia & tamu', 'volume' => 150, 'satuan' => 'kotak', 'harga_satuan' => 20000, 'jumlah_rencana' => 3000000, 'realisasi' => 2850000, 'pj' => 'Maya Anggraini', 'status' => 'selesai', 'catatan' => null],
            ['kategori' => 'Sound System & Hiburan', 'nama_item' => 'Sewa sound system + organ tunggal', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 3500000, 'jumlah_rencana' => 3500000, 'realisasi' => 3500000, 'pj' => 'Rina Wulandari', 'status' => 'selesai', 'catatan' => null],
            ['kategori' => 'Lomba & Hadiah', 'nama_item' => 'Hadiah & doorprize lomba anak', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 1500000, 'jumlah_rencana' => 1500000, 'realisasi' => 1200000, 'pj' => 'Rina Wulandari', 'status' => 'proses', 'catatan' => null],
            ['kategori' => 'Perlengkapan & Dekorasi', 'nama_item' => 'Umbul-umbul & bendera merah putih', 'volume' => 50, 'satuan' => 'pcs', 'harga_satuan' => 15000, 'jumlah_rencana' => 750000, 'realisasi' => 0, 'pj' => 'Joko Prasetyo', 'status' => 'belum', 'catatan' => null],
            ['kategori' => 'Dana Cadangan/Tak Terduga', 'nama_item' => 'Cadangan dana tak terduga', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 500000, 'jumlah_rencana' => 500000, 'realisasi' => 0, 'pj' => 'Andi Saputra', 'status' => 'belum', 'catatan' => null],
        ];

        foreach ($rabItems as $item) {
            RabItem::updateOrCreate(
                ['kategori' => $item['kategori'], 'nama_item' => $item['nama_item']],
                $item
            );
        }
    }
}
