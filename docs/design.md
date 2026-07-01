# Awan Gym — UI/UX Design Direction

> Dokumen arahan desain untuk Codex. Target utama adalah aplikasi web **mobile-first** dengan karakter sporty, merah, modern, minimalis, dan mudah dipakai. Gunakan desain ini sebagai baseline sebelum menambah variasi visual baru.

## 1. Konsep Desain

### Nama konsep

**Sporty Glass Fitness**

### Karakter yang ingin dibangun

- Enerjik dan sporty, tetapi tidak ramai.
- Modern dan premium melalui aksen *liquid glass* yang halus.
- Cepat dipahami oleh member pemula.
- Memprioritaskan status membership dan program latihan.
- Nyaman dipakai satu tangan pada ponsel.

### Prinsip utama

1. **Mobile first, desktop enhanced.** Rancang layar ponsel terlebih dahulu; desktop hanya memperluas layout yang sama.
2. **Red as an accent, not a flood.** Merah adalah identitas dan aksen prioritas, bukan warna semua elemen.
3. **Glass only where it adds value.** Efek liquid glass digunakan pada hero card, bottom navigation, modal, chip/filter, dan tombol floating; jangan gunakan pada form, tabel data, atau teks penting yang butuh keterbacaan tinggi.
4. **Membership status must be unmistakable.** Dalam sekali lihat, user/pegawai harus tahu status aktif atau tidak aktif.
5. **Cards over dense tables.** Di ponsel, tampilkan data dalam card/list; tabel lebar hanya boleh dipakai pada desktop atau diubah menjadi kartu responsif.

## 2. Target Layar dan Responsivitas

### Prioritas ukuran layar

| Prioritas | Lebar | Perlakuan |
|---|---:|---|
| Ponsel kecil | 360 px | Harus tetap rapi dan tidak horizontal-scroll |
| Ponsel utama | 390–430 px | Target desain utama |
| Tablet | 768 px+ | Tambah ruang/grid secukupnya |
| Desktop | 1024 px+ | Bisa gunakan sidebar dan tabel penuh bila relevan |

### Aturan responsive

- Gunakan area konten utama maksimal sekitar 480–520 px pada mobile agar tetap terasa seperti aplikasi, bukan halaman web kosong.
- Bottom navigation harus tetap fixed atau sticky pada mobile dan memberi safe padding di bawah konten.
- Target sentuh minimum: **44 × 44 px**.
- Gunakan ruang antar elemen yang lega; jangan memaksa banyak informasi pada satu baris.
- Hindari hover sebagai satu-satunya cara memahami aksi; semua aksi harus dapat dipakai dengan tap.

## 3. Color Palette

| Token | Hex / nilai | Penggunaan |
|---|---|---|
| `--color-primary` | `#E52335` | Tombol utama, item aktif, highlight, progress |
| `--color-primary-dark` | `#A90F20` | Hover/pressed, gradien, teks merah gelap |
| `--color-primary-soft` | `#FF6B78` | Aksen sekunder dan gradien halus |
| `--color-ink` | `#111214` | Teks utama, permukaan gelap, sidebar desktop |
| `--color-surface` | `#FFFFFF` | Card informasi, form, background konten |
| `--color-background` | `#F5F6F8` | Background utama mode terang |
| `--color-muted` | `#6B7280` | Teks sekunder dan icon tidak aktif |
| `--color-border` | `#E5E7EB` | Border ringan |
| `--color-success` | `#24C96B` | Membership aktif |
| `--color-warning` | `#F5A524` | Membership segera berakhir |
| `--color-danger` | `#E5484D` | Kedaluwarsa, gagal, tindakan destruktif |

### Gradien utama

Gunakan dengan hemat pada hero membership card atau CTA besar:

```css
background: linear-gradient(135deg, #A90F20 0%, #E52335 52%, #FF6B78 100%);
```

## 4. Typography

- Gunakan **Poppins** untuk heading, angka besar, dan label hero.
- Gunakan **Inter** untuk body, form, tabel, dan informasi detail.
- Jika hanya satu font tersedia, gunakan Inter untuk konsistensi dan performa.

### Skala mobile yang disarankan

| Elemen | Ukuran | Weight |
|---|---:|---:|
| Hero/title utama | 24–28 px | 700 |
| Page title | 20–24 px | 700 |
| Card title | 16–18 px | 600–700 |
| Body | 14–16 px | 400–500 |
| Label / caption | 12–13 px | 500–600 |
| Angka status / sisa hari | 24–32 px | 700 |

Gunakan line-height lega. Jangan menggunakan teks kecil di bawah 12 px untuk informasi penting.

## 5. Liquid Glass Guidelines

Efek *liquid glass* harus terlihat modern, tetapi tetap aman untuk keterbacaan dan performa.

### Boleh digunakan untuk

- Kartu membership digital.
- Bottom navigation mobile.
- Modal atau bottom sheet.
- Filter chip / segmented control.
- Floating action button.
- Hero/header yang memiliki background visual.

### Jangan digunakan untuk

- Form input utama.
- Isi tabel transaksi.
- Daftar data panjang.
- Teks status kritis tanpa background kontras.
- Seluruh background halaman.

### Formula visual dasar

```css
.glass {
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow: 0 12px 34px rgba(17, 18, 20, 0.14);
  backdrop-filter: blur(18px) saturate(140%);
  -webkit-backdrop-filter: blur(18px) saturate(140%);
}
```

### Fallback dan aksesibilitas

- Sediakan background solid bila browser tidak mendukung `backdrop-filter`.
- Pastikan kontras teks memenuhi minimal 4.5:1 untuk teks normal.
- Jangan memakai teks abu transparan di atas foto atau gradien tanpa overlay yang cukup.
- Hormati `prefers-reduced-motion`; animasi glass, shimmer, atau floating harus dapat dikurangi/dimatikan.

## 6. Spacing, Shape, dan Elevation

### Spacing scale

Gunakan skala konsisten: `4, 8, 12, 16, 20, 24, 32` px.

### Radius

| Komponen | Radius |
|---|---:|
| Button / input | 12–14 px |
| Card biasa | 16 px |
| Hero membership card | 20–24 px |
| Chip / badge | 999 px |
| Bottom sheet | 24–28 px (atas) |

### Elevation

- Gunakan bayangan lembut, tidak hitam pekat.
- Card standar: border abu terang + shadow sangat ringan.
- Hero card: shadow lebih kuat, tapi tetap halus.
- Hindari lebih dari dua tingkat shadow dalam satu layar.

## 7. Navigasi Mobile

Gunakan bottom navigation. Maksimum lima item dan gunakan label teks, bukan icon saja.

### Menu Member

| Label | Tujuan |
|---|---|
| Beranda | Kartu membership dan ringkasan |
| Membership | Detail paket dan masa aktif |
| Program | Program latihan aktif |
| Pembayaran | Riwayat transaksi |
| Profil | Profil akun |

### Menu Admin

| Label | Tujuan |
|---|---|
| Beranda | Ringkasan operasional |
| Member | Kelola member |
| Transaksi | Pendaftaran, perpanjangan, pembayaran |
| Laporan | Pemasukan dan ringkasan |
| Profil | Profil akun |

### Menu Personal Trainer

| Label | Tujuan |
|---|---|
| Beranda | Ringkasan member binaan dan program |
| Member | Member binaan |
| Program | Program dan detail latihan |
| Profil | Profil akun |

Pada desktop, bottom navigation dapat berubah menjadi sidebar kiri, tetapi informasi dan urutan tugas tetap sama.

## 8. Screen Direction per Role

### 8.1 Login

**Tujuan:** cepat, bersih, dan terasa premium.

- Latar putih/abu terang dengan aksen gradien merah yang tidak mengganggu.
- Logo Awan Gym di atas.
- Heading singkat: `Train Strong. Stay Consistent.`
- Input email dan password pada card putih solid.
- CTA merah penuh: `Masuk`.
- Error login ditampilkan dekat input, jelas, dan tidak hanya dibedakan lewat warna.
- Pada desktop, boleh gunakan split layout dengan visual gym di sisi kiri; mobile cukup gunakan gambar/gradien kecil sebagai hero agar form cepat dijangkau.

### 8.2 Dashboard Member — Prioritas Tertinggi

**Tujuan:** Member dapat membuka aplikasi dan menunjukkan status keanggotaan dalam beberapa detik.

Urutan konten:

1. Header kecil: logo, sapaan, avatar/profil.
2. **Digital Membership Card** besar.
3. Ringkasan masa aktif dan sisa hari.
4. Program latihan aktif.
5. Personal trainer yang menangani.
6. Pembayaran terakhir.

#### Digital Membership Card

Gunakan gradien merah, bentuk rounded besar, dan overlay glass lembut. Konten wajib:

```text
AWAN GYM
DIGITAL MEMBERSHIP

[foto] Nama Member
       AGM-001

● MEMBERSHIP AKTIF
Berlaku hingga 30 September 2026

Paket: Membership 3 Bulan
```

Aturan visual:

- Status harus sangat menonjol.
- Gunakan badge hijau untuk aktif, kuning untuk segera berakhir, merah untuk kedaluwarsa.
- Jangan menyembunyikan tanggal berakhir di dalam menu atau tooltip.
- Foto member harus jelas agar pegawai dapat mencocokkan identitas.
- Kartu tidak membutuhkan QR code untuk MVP karena verifikasi dilakukan manual.

#### Card ringkasan masa aktif

```text
29 hari tersisa
[===========----] 72%
Masa aktif berakhir 30 Sep 2026
```

Gunakan progress bar merah atau gradien lembut; untuk status mendekati habis gunakan oranye.

### 8.3 Detail Membership Member

Gunakan card informasi solid, bukan full glass:

- Paket aktif.
- Tanggal mulai dan tanggal berakhir.
- Status membership.
- Riwayat subscription terdahulu.
- CTA konteks: `Hubungi Admin untuk Perpanjangan` atau informasi perpanjangan; jangan mengklaim self-service renewal bila belum dibangun.

### 8.4 Program Latihan Member

Program latihan harus mudah dibaca oleh pemula.

- Gunakan tab/segmented control `Minggu 1`, `Minggu 2`, dan seterusnya, atau accordion per hari.
- Setiap latihan tampil sebagai card kecil:
  - Thumbnail/gambar latihan bila tersedia.
  - Nama latihan.
  - Set × repetisi atau durasi.
  - Waktu istirahat.
  - Tombol `Lihat Cara Latihan` bila instruksi/video tersedia.
- Jangan tampilkan tabel lebar.

Contoh struktur:

```text
Minggu 1 — Hari 1
Upper Body

[ Push Up ]
3 set × 10 repetisi
Istirahat 60 detik

[ Dumbbell Row ]
3 set × 12 repetisi
Istirahat 60 detik
```

### 8.5 Dashboard Admin

**Tujuan:** tindakan operasional cepat dari ponsel.

Bagian atas: ringkasan dalam card/grid kecil:

- Member aktif.
- Membership akan berakhir.
- Pendapatan bulan ini.
- Pembayaran menunggu verifikasi.

Quick actions:

- `+ Tambah Member`
- `+ Buat Transaksi`
- `+ Perpanjang Membership`

Daftar member pada mobile menggunakan card:

```text
[Foto] Enzy Faniko
AGM-001
Paket 3 Bulan · Berakhir 30 Sep 2026
[ Aktif ]                          [Detail]
```

Jangan gunakan tabel spreadsheet-style pada mobile.

### 8.6 Transaksi dan Perpanjangan Admin

Buat sebagai alur bertahap/bottom sheet agar tidak membingungkan:

1. Pilih/cari member.
2. Pilih paket.
3. Tampilkan tanggal mulai-berakhir yang dihitung sistem.
4. Pilih metode pembayaran.
5. Konfirmasi nominal dan status pembayaran.
6. Tampilkan invoice berhasil dibuat.

Tampilkan informasi penting sebelum simpan, terutama:

- Nama member.
- Paket.
- Durasi.
- Nominal.
- Periode aktif.
- Status pembayaran.

### 8.7 Laporan Admin

Mobile report harus ringkas:

- Filter bulan/periode.
- Card total pemasukan.
- Jumlah transaksi paid.
- Daftar transaksi terbaru.
- Chart sederhana hanya bila benar-benar membantu.

Pada desktop, tabel dapat ditampilkan penuh. Pada mobile, gunakan card transaksi dan filter bottom sheet.

### 8.8 Dashboard dan Program Personal Trainer

Dashboard trainer:

- Jumlah member binaan.
- Program aktif.
- Program yang selesai.
- Daftar member binaan yang butuh perhatian.

Card member binaan:

```text
[Foto] Enzy Faniko
Beginner Full Body
Progress: 70%
[Kelola Program]
```

Halaman program trainer:

- Daftar program dalam card.
- CTA utama: `+ Buat Program`.
- Edit detail latihan memakai list reorder-friendly pada mobile.
- Input set, repetisi, durasi, dan rest dibuat ringkas serta jelas.

## 9. Component Library

Bangun komponen reusable berikut:

- `AppShell`
- `TopBar`
- `BottomNavigation`
- `GlassCard`
- `MembershipCard`
- `StatusBadge`
- `MetricCard`
- `SectionHeader`
- `MemberListCard`
- `ProgramCard`
- `ExerciseCard`
- `TransactionCard`
- `EmptyState`
- `LoadingSkeleton`
- `ErrorState`
- `ConfirmationSheet`
- `DateRangeFilter`
- `SearchField`
- `PrimaryButton`, `SecondaryButton`, `DangerButton`

### Status Badge semantic mapping

| Status | Label | Warna |
|---|---|---|
| Active | `Aktif` | Hijau |
| Expiring soon | `Segera Berakhir` | Oranye |
| Expired | `Kedaluwarsa` | Merah |
| Inactive | `Tidak Aktif` | Abu/gelap |
| Pending payment | `Menunggu Pembayaran` | Kuning/abu |

Jangan hanya mengandalkan warna; selalu sertakan label teks dan icon bila relevan.

## 10. Interaction and Motion

- Gunakan transisi pendek: 150–220 ms.
- Gunakan feedback tekan/pressed pada button dan card yang dapat di-tap.
- Hindari animasi besar atau scrolling parallax yang mengganggu.
- Badge status dan angka membership tidak perlu animasi berulang.
- Loading gunakan skeleton sederhana, bukan spinner penuh layar kecuali proses memang blocking.

## 11. Forms and Validation

- Input field harus solid/opaque agar terbaca.
- Label harus selalu terlihat; jangan hanya memakai placeholder.
- Validasi tampil inline setelah field dan jelaskan cara memperbaikinya.
- Gunakan keyboard type yang tepat untuk email, angka telepon, nominal, dan tanggal.
- Field wajib diberi indikator yang jelas.
- Tombol submit harus disabled/loading saat request berlangsung untuk mencegah double submit.

## 12. Accessibility Checklist

- Kontras warna minimum 4.5:1 untuk teks normal.
- Ukuran tap target minimum 44 px.
- Semua icon button memiliki `aria-label`/tooltip yang sesuai.
- Urutan fokus keyboard logis pada desktop.
- Error form tidak hanya dibedakan warna.
- Informasi status membership dapat dibaca screen reader dengan label yang eksplisit.
- Hindari font terlalu kecil dan transparansi teks yang berlebihan.

## 13. Do / Don't

### Do

- Utamakan satu aksi utama per layar.
- Gunakan merah pada CTA, progress, dan highlight.
- Gunakan white cards untuk detail/transaksi agar mudah dibaca.
- Beri ruang kosong yang cukup.
- Buat kartu membership sebagai elemen visual paling kuat bagi member.

### Don't

- Jangan membuat seluruh halaman merah.
- Jangan memakai glass effect di semua card.
- Jangan memasukkan tabel horizontal ke mobile.
- Jangan menyembunyikan status membership atau tanggal berakhir.
- Jangan memakai banyak jenis font, icon set, atau radius yang tidak konsisten.
- Jangan menambahkan QR attendance, scanner, atau check-in karena di luar scope MVP.

## 14. Definition of Done untuk UI

UI dapat dianggap selesai bila:

- Tampilan utama nyaman pada lebar 360 px tanpa horizontal scroll.
- Digital membership card dapat dibaca dalam satu layar dan statusnya langsung jelas.
- Setiap role mendapatkan navigation dan dashboard sesuai tugasnya.
- Form pendaftaran/perpanjangan dapat diselesaikan dari ponsel dengan mudah.
- Daftar member, transaksi, dan program menggunakan card responsif pada mobile.
- Glass effect tetap kontras, punya fallback, dan tidak mengganggu performa atau keterbacaan.
- Desktop tetap rapi, tetapi tidak mengorbankan pola interaksi mobile-first.
