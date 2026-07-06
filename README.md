# merdeka-event

Portal acara warga bertema kemerdekaan RI berbasis Laravel 13 + Livewire 4. Project ini dipakai untuk mengelola acara 17-an warga dengan halaman publik yang transparan dan dashboard admin/panitia untuk operasional harian.

## Fitur Utama

- Halaman publik untuk:
  - susunan panitia
  - daftar lomba
  - transparansi dana masuk dan dana keluar
  - form warga untuk iuran, donasi, sponsor, dan pendaftaran anggota keluarga
- Dashboard admin/panitia untuk:
  - pengaturan acara
  - pengaturan website
  - susunan panitia
  - lomba dan peserta
  - review form warga
  - pencatatan dana masuk dan dana keluar

## Stack

- PHP 8.3
- Laravel 13
- Livewire 4
- Tailwind CSS 4
- Vite
- Spatie Permission

## Alur Data Singkat

- Warga mengisi `Form Warga` dari halaman publik.
- Data kontribusi akan masuk sebagai pengajuan dan bisa diverifikasi panitia.
- Setelah diverifikasi, data pemasukan dan peserta lomba akan tercatat ke sistem.
- Admin/panitia juga bisa menambah transaksi manual untuk dana masuk maupun dana keluar.
- Halaman `Transparansi Dana` publik akan membaca transaksi tersebut agar warga bisa melihat pemasukan dan pengeluaran secara terbuka.

## Setup Lokal

1. Install dependency backend:

```bash
composer install
```

2. Install dependency frontend:

```bash
npm install
```

3. Salin file environment:

```bash
copy .env.example .env
```

4. Generate app key:

```bash
php artisan key:generate
```

5. Jalankan migrasi dan seed dummy data:

```bash
php artisan migrate --seed
```

6. Build asset:

```bash
npm run build
```

7. Jalankan server lokal:

```bash
php artisan serve
```

## Akun Dummy

Login admin default hasil seed:

- Username: `superadmin`
- Password: `password`

## Route Penting

- Publik: `/`
- Transparansi dana: `/transparansi`
- Form warga: `/form-warga`
- Login panitia: `/login`
- Dashboard admin: `/admin`

## Catatan

- Data awal di seed hanya untuk uji coba lokal.
- Informasi website seperti nama acara, kontak, dan rekening bisa diubah dari dashboard `Pengaturan Website`.
- Nominal input sudah diformat ke format Indonesia agar lebih nyaman dipakai warga dan panitia.
