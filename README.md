# Sistem Keuangan PAC IPNU IPPNU Cikarang Utara

Aplikasi pencatatan keuangan berbasis **PHP native + MySQL**, khusus admin.

## Fitur
- Login admin (password di-hash, batas 5x salah login, proteksi CSRF)
- Dashboard: total saldo, **saldo Cash**, **saldo QRIS**, pemasukan/pengeluaran bulan ini, transaksi terbaru
- Pemasukan & pengeluaran: tanggal, nominal, metode Cash/QRIS, **nama anggota pembayar**, keterangan; bisa ubah & hapus
- Filter per jenis, metode, bulan, dan pencarian nama/keterangan
- Laporan bulanan + **unduh PDF** (kop berlogo, ringkasan Cash/QRIS, rincian, tanda tangan)
- Ganti nama & kata sandi admin

## Kebutuhan
PHP 8.0+ (ekstensi `pdo_mysql`, `iconv`) dan MySQL/MariaDB. Bisa pakai XAMPP/Laragon.

## Cara pasang
1. Salin folder `keuangan-pac` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, **Import** file `database.sql`.
3. Sesuaikan `config.php` (user/password database, nama ketua & bendahara, alamat sekretariat).
4. Buka `http://localhost/keuangan-pac`.
5. Login: **admin / admin123**, lalu **segera ganti kata sandi** di menu *Akun Admin*.

## Struktur
```
config.php            pengaturan database & data organisasi
database.sql          struktur tabel + akun admin awal
login.php, logout.php
dashboard.php
transaksi.php         daftar + filter
transaksi_form.php    tambah / ubah transaksi
hapus.php
laporan.php           laporan bulanan
laporan_pdf.php       cetak PDF (FPDF)
akun.php              ganti nama & kata sandi
includes/             bootstrap (helper), header, footer
assets/               CSS & logo
lib/fpdf/             library FPDF (lisensi bebas)
```
