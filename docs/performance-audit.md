# Analisis waktu muat Awan Gym

Pengukuran lokal pada 25 Oktober 2026 menggunakan PHP 8.3.23, MySQL,
Laravel 13, dan server development di `127.0.0.1:8000`.
Angka ini mengukur respons server lokal, bukan LCP atau pengalaman jaringan pengguna produksi.

## Temuan utama: PHP tanpa OPcache

Ekstensi OPcache tidak dimuat oleh PHP yang digunakan pada pemeriksaan ini.
Autoload Composer memakan 145 ms dan bootstrap Laravel 312 ms pada satu sampel.
Login pada tiga pengukuran berurutan membutuhkan TTFB 551, 530, dan 568 ms.
Sebelumnya ada satu sampel 3.244 ms; penyebab lonjakan tersebut belum terisolasi.

Pada proses pembanding dengan OPcache aktif, request pertama membutuhkan 624 ms,
lalu request berikutnya 41, 43, dan 43 ms. Artinya, pada pengujian lokal ini
respons setelah cache terisi sekitar 13 kali lebih cepat. Request pertama tetap lebih lambat.
Server utama dan konfigurasi PHP global tidak diubah.

### Solusi untuk development lokal

Aktifkan OPcache pada PHP yang benar-benar melayani aplikasi, lalu restart server PHP.
File konfigurasi PHP CLI saat pemeriksaan:
`C:\Users\Gung Aria\AppData\Local\Programs\PhpWebStudy-Data\env\php\php.ini`.
Konfigurasi PHP web server lain dapat berbeda.

```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0
```

`enable_cli=1` diperlukan untuk server bawaan PHP yang dijalankan dari CLI.
Pemeriksaan timestamp tetap aktif agar perubahan kode development terbaca.
Pastikan ekstensi tidak dimuat dua kali jika konfigurasi sudah diperbarui.

Untuk mencoba tanpa mengubah php.ini, dari root project di PowerShell:

```powershell
Push-Location public
try {
    php -d zend_extension=opcache -d opcache.enable_cli=1 -d opcache.validate_timestamps=1 -d opcache.revalidate_freq=0 -S 127.0.0.1:8001 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
} finally {
    Pop-Location
}
```

Buka `http://127.0.0.1:8001/login` beberapa kali; jalankan Vite di terminal
terpisah dengan `npm run dev` jika memakai aset development.
Perintah ini untuk development lokal saja.

## Perbaikan kode yang sudah diterapkan

1. `resources/js/app.js`: Chart.js sebelumnya diimpor pada semua halaman,
   termasuk login. Sekarang library dimuat secara dinamis hanya ketika ada
   canvas dashboard, termasuk sesudah navigasi Livewire.
   Build lama memiliki entry JS 202.473 byte; entry baru sekitar 1,61 KB
   (0,85 KB gzip). Chunk grafik sekitar 202,24 KB tetap dimuat pada dashboard.
2. `DashboardController`: enam query SUM pendapatan bulanan digabung menjadi
   satu query agregasi dengan parameter binding dan rentang tanggal yang sama.
   Rentang dihitung dari awal bulan agar tanggal 29–31 tidak melompati bulan
   yang lebih pendek. Bulan tanpa pembayaran tetap menghasilkan nol.
3. Dashboard member tidak lagi mengambil seluruh riwayat subscription yang
   tidak dipakai oleh tampilan. Relasi `trainerAddon` juga dimuat bersama
   subscription aktif untuk menghindari query tambahan saat render.

Pada data lokal (7 user, 4 member, 4 subscription, 4 transaksi), dashboard admin
turun dari 20 menjadi 15 query. Waktu SQL sebelum perubahan sekitar 13 ms;
setelah perubahan sekitar 11 ms. Ini optimasi tambahan; database kecil ini
tidak menjelaskan sebagian besar waktu tunggu saat ini.

## Deployment production

Lingkungan yang diperiksa memakai `APP_ENV=local`, `APP_DEBUG=true`, dan Vite
development aktif. Ini sesuai untuk development, tetapi production perlu
konfigurasi dan aset build sendiri:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Pada proses deployment, setelah environment dan dependency siap:

```sh
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize
```

Pastikan `public/hot` tidak ikut deployment: file tersebut membuat Laravel
mengarahkan aset ke Vite development. Saat pemeriksaan isinya mengarah ke
`http://[::1]:5173`, yang hanya cocok untuk komputer lokal. Vite lokal merespons
sekitar 2 ms sehingga tidak terbukti sebagai hambatan pada pengujian ini.
Aktifkan OPcache pada PHP web server production dan compression HTTP untuk
CSS/JS. Konfigurasi/route aplikasi lokal belum dicache; jangan menjadikan
cache production sebagai pengaturan permanen selama pengembangan aktif.

Panduan resmi: https://laravel.com/docs/13.x/deployment#optimization

## Prioritas berikutnya jika data membesar

- Logo `public/logo/logo.png` berukuran 838.651 byte untuk tampilan kecil.
  Siapkan varian WebP/AVIF dengan dimensi sesuai tampilan.
- Laporan pemasukan mengambil semua transaksi bulan terpilih lalu menghitung
  statistik di PHP. Gunakan pagination untuk tabel dan agregasi SQL terpisah
  untuk statistik seluruh bulan; PDF tetap perlu seluruh hasil periode.
- Pilihan member pada formulir transaksi mengambil semua member setiap render.
  Gunakan pencarian server dengan hasil terbatas saat jumlah member bertambah.
- Daftar member memuat seluruh riwayat subscription per member meskipun tabel
  dipaginasi. Batasi relasi sesuai data yang ditampilkan.
- Filter status/tanggal pembayaran sudah mempunyai indeks gabungan; penambahan
  indeks lain sebaiknya berdasarkan EXPLAIN dan pengukuran data representatif.

## Validasi

- `npm run build` berhasil.
- Tiga test dashboard lulus dengan delapan assertion, termasuk batas awal/akhir
  bulan, pergantian tahun, bulan kosong, pembayaran pending, growth, dan satu
  query agregasi pendapatan.
- Pemeriksaan kode menggunakan Laravel Pint pada file PHP yang diubah.
- Belum dilakukan audit browser/Lighthouse atau pengukuran server production.
