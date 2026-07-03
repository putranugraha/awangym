# Awan Gym — Product & Implementation Plan

> Dokumen konteks untuk Codex. Gunakan dokumen ini sebagai sumber kebenaran utama ketika membangun Web Awan Gym. Jangan menambahkan fitur, tabel, atau alur bisnis baru di luar dokumen ini tanpa alasan teknis yang jelas.

## 1. Ringkasan Produk

**Awan Gym** adalah aplikasi web mobile-first untuk membantu operasional gym dan memberi pengalaman mandiri bagi member. Sistem dipakai terutama melalui ponsel, tetapi tetap harus responsif di tablet dan desktop.

Tujuan utama sistem:

1. Memudahkan admin/pegawai mengelola pendaftaran member, paket membership, perpanjangan masa aktif, pembayaran, dan laporan keuangan.
2. Memungkinkan member melihat profil digital, status keanggotaan, masa aktif, riwayat pembayaran, serta program latihan.
3. Memungkinkan personal trainer membuat program latihan, menyusun detail latihan, dan menetapkan program kepada member.
4. Menyediakan **digital membership card** yang dapat ditunjukkan member kepada pegawai di loket untuk diverifikasi **secara manual**.

## 2. Peran Pengguna

| Role | Tujuan utama | Akses inti |
|---|---|---|
| `admin` | Mengelola operasional gym | Member, paket membership, pendaftaran/perpanjangan, pembayaran, laporan, akun personal trainer |
| `member` | Melihat status membership dan program latihan | Profil sendiri, kartu membership digital, program latihan, riwayat pembayaran |
| `personal_trainer` | Mengelola latihan member | Profil sendiri, daftar member binaan, program latihan, detail latihan, penetapan program |

### Batasan hak akses

- Member **hanya** boleh melihat data miliknya sendiri.
- Personal trainer tidak boleh mengubah data membership, transaksi pembayaran, atau laporan keuangan.
- Admin dapat mengelola data operasional dan melakukan verifikasi pembayaran.
- Gunakan role-based authorization di sisi server, bukan hanya menyembunyikan menu di UI.

## 3. Alur Bisnis Utama

### 3.1 Pendaftaran member baru

1. Admin membuat akun pengguna dengan role `member`.
2. Admin melengkapi profil member dan sistem membuat `member_code` yang unik.
3. Admin memilih paket membership.
4. Sistem membuat riwayat subscription bertipe `new_registration` dan transaksi pembayaran.
5. Setelah pembayaran berstatus `paid`, membership dapat ditetapkan aktif.
6. Member login dan dapat melihat kartu membership digitalnya.

### 3.2 Perpanjangan membership

1. Admin memilih member dan paket baru/lanjutan.
2. Sistem membuat subscription baru bertipe `renewal` serta transaksi pembayaran terkait.
3. Hanya transaksi `paid` yang dihitung sebagai pemasukan dan mengaktifkan membership.
4. Rekomendasi aturan tanggal:
   - Jika perpanjangan dilakukan sebelum masa aktif berakhir, periode baru dimulai **sehari setelah `end_date` subscription terakhir** agar sisa hari tidak hilang.
   - Jika membership sudah kedaluwarsa, periode baru dimulai pada tanggal pembayaran atau tanggal mulai yang dipilih admin.
5. Riwayat subscription lama tidak boleh ditimpa atau dihapus.

### 3.3 Verifikasi manual di loket

Tidak ada fitur check-in/check-out atau pencatatan attendance pada MVP.

Alur:

1. Member login.
2. Member membuka Dashboard atau halaman Membership.
3. Sistem menampilkan **kartu membership digital** dengan foto, nama, kode member, paket, status, dan tanggal berakhir.
4. Pegawai/admin di loket memeriksa layar dan mencocokkan identitas member secara manual.
5. Member dapat masuk apabila status yang tampak adalah **Aktif**.

Informasi minimum yang wajib terlihat jelas di kartu:

- Foto profil member
- Nama lengkap
- Kode member
- Nama paket aktif
- Badge status membership
- Tanggal berlaku hingga

### 3.4 Program latihan

1. Sistem menyediakan katalog exercise dan program Gym Beginner serta Gym Strength melalui seeder.
2. Admin menetapkan satu program aktif kepada member dan dapat memilih personal trainer secara opsional.
3. Member tanpa personal trainer tetap dapat menjalankan dan melihat program secara mandiri.
4. Personal trainer hanya melihat member yang ditugaskan kepadanya dan memvalidasi gerakan yang dilakukan bersama.
5. Sistem tidak mencatat beban atau repetisi aktual; validasi PT hanya berupa checklist selesai.
6. Member melihat program dengan format ramah ponsel: per minggu/hari, card atau accordion, bukan tabel lebar.

### 3.5 Keuangan

- Sumber laporan keuangan adalah `payment_transactions`.
- Pemasukan hanya dihitung dari transaksi dengan `payment_status = paid`.
- Transaksi `pending`, `failed`, dan `refunded` tidak dihitung sebagai pemasukan aktif.
- Laporan tidak membutuhkan tabel database sendiri; laporan dibuat dari hasil agregasi transaksi.

## 4. Scope MVP

### Wajib ada

- Autentikasi login dan logout.
- Role: Admin, Member, Personal Trainer.
- Profil pengguna.
- Pengelolaan data member oleh admin.
- Pengelolaan akun personal trainer oleh admin.
- Paket membership.
- Pendaftaran membership dan perpanjangan membership.
- Pencatatan dan verifikasi pembayaran.
- Digital membership card untuk member.
- Status membership otomatis: aktif, mendekati habis, kedaluwarsa, atau tidak aktif.
- Daftar latihan (`exercises`).
- Program latihan dan detail latihan.
- Penetapan program latihan dari personal trainer kepada member.
- Riwayat pembayaran member.
- Laporan pemasukan dasar untuk admin.

### Tidak termasuk pada MVP

- Attendance/check-in/check-out.
- QR code scanner, gate otomatis, atau integrasi perangkat akses.
- Booking sesi personal trainer.
- Emergency contact member.
- Specialization atau certification personal trainer.
- Payment gateway online; metode pembayaran dapat dicatat dan diverifikasi manual terlebih dahulu.
- Video hosting internal; gunakan URL video bila fitur video digunakan.
- Notifikasi push/email/SMS otomatis.

## 5. Struktur Database

Gunakan nama tabel dan relasi ini. Field audit `created_at` dan `updated_at` digunakan pada seluruh tabel yang relevan.

### 5.1 `users`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `user_id` | PK, bigint/uuid | Identitas akun |
| `full_name` | varchar(100) | Nama lengkap |
| `email` | varchar(100), unique | Email login |
| `phone` | varchar(20) | Nomor telepon |
| `password` | varchar(255) | Selalu hash; jangan simpan plaintext |
| `role` | enum | `admin`, `member`, `personal_trainer` |
| `account_status` | enum | `active`, `inactive` |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.2 `members`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `member_id` | PK | Identitas member |
| `user_id` | FK -> `users.user_id`, unique | Satu profil member untuk satu akun member |
| `member_code` | varchar(20), unique | Contoh: `AGM-001` |
| `gender` | enum | `L`, `P` |
| `birth_date` | date | Tanggal lahir |
| `address` | text | Alamat |
| `profile_photo` | varchar(255), nullable | URL/path foto untuk verifikasi loket |
| `registered_at` | date | Tanggal pertama daftar |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.3 `personal_trainers`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `trainer_id` | PK | Identitas trainer |
| `user_id` | FK -> `users.user_id`, unique | Satu profil trainer untuk satu akun trainer |
| `trainer_code` | varchar(20), unique | Kode trainer |
| `profile_photo` | varchar(255), nullable | URL/path foto |
| `bio` | text, nullable | Deskripsi singkat trainer |
| `employment_status` | enum | `active`, `inactive` |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

> Jangan tambahkan field `specialization` atau `certification`.

### 5.4 `membership_packages`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `package_id` | PK | Identitas paket |
| `package_name` | varchar(100) | Nama paket |
| `duration_months` | integer | Durasi paket dalam bulan |
| `price` | decimal(12,2) | Harga paket |
| `description` | text, nullable | Keterangan paket |
| `package_status` | enum | `active`, `inactive` |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.5 `membership_subscriptions`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `subscription_id` | PK | Identitas riwayat membership |
| `member_id` | FK -> `members.member_id` | Pemilik membership |
| `package_id` | FK -> `membership_packages.package_id` | Paket yang dipilih |
| `created_by` | FK -> `users.user_id` | Admin yang menginput |
| `subscription_type` | enum | `new_registration`, `renewal` |
| `start_date` | date | Mulai berlaku |
| `end_date` | date | Berakhir berlaku |
| `subscription_status` | enum | `active`, `expired`, `cancelled` |
| `notes` | text, nullable | Catatan |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.6 `payment_transactions`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `transaction_id` | PK | Identitas transaksi |
| `invoice_number` | varchar(30), unique | Nomor invoice |
| `member_id` | FK -> `members.member_id` | Pembayar |
| `subscription_id` | FK -> `membership_subscriptions.subscription_id` | Subscription terkait |
| `amount` | decimal(12,2) | Nominal pembayaran |
| `payment_method` | enum | `cash`, `transfer`, `e_wallet` |
| `payment_status` | enum | `pending`, `paid`, `failed`, `refunded` |
| `payment_date` | timestamp, nullable | Waktu pembayaran/konfirmasi |
| `verified_by` | FK -> `users.user_id`, nullable | Admin yang memverifikasi |
| `notes` | text, nullable | Catatan |
| `created_at` | timestamp | Audit |

### 5.7 `exercises`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `exercise_id` | PK | Identitas gerakan |
| `exercise_name` | varchar(100) | Nama latihan |
| `category` | varchar(100) | Contoh: kardio, dada, kaki |
| `description` | text | Penjelasan latihan |
| `instruction` | text | Cara melakukan latihan |
| `image_url` | varchar(255), nullable | Gambar latihan |
| `video_url` | varchar(255), nullable | Link video latihan |
| `exercise_status` | enum | `active`, `inactive` |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.8 `workout_programs`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `program_id` | PK | Identitas program |
| `program_code` | varchar(50), unique | Kode katalog program |
| `program_name` | varchar(150) | Nama program |
| `target_goal` | varchar(100) | Contoh: weight loss, muscle gain |
| `difficulty_level` | enum | `beginner`, `intermediate`, `advanced` |
| `duration_weeks` | integer | Durasi dalam minggu |
| `description` | text | Penjelasan program |
| `source_name` | varchar(255), nullable | Referensi sumber program |
| `source_reference` | varchar(255), nullable | Bagian sumber yang diadaptasi |
| `program_status` | enum | `active`, `inactive` |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.9 `program_exercises`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `program_exercise_id` | PK | Identitas detail program |
| `program_id` | FK -> `workout_programs.program_id` | Program terkait |
| `exercise_id` | FK -> `exercises.exercise_id` | Gerakan terkait |
| `training_day` | integer | Hari latihan keberapa |
| `session_name` | varchar(100), nullable | Nama sesi latihan |
| `sequence_order` | integer | Urutan gerakan |
| `sets` | integer, nullable | Jumlah set |
| `repetitions` | varchar(50), nullable | Contoh: `10–12` |
| `duration_minutes` | integer, nullable | Durasi bila berbasis waktu |
| `rest_seconds` | integer, nullable | Istirahat |
| `intensity` | varchar(100), nullable | Panduan intensitas |
| `notes` | text, nullable | Catatan |

### 5.10 `member_programs`

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `member_program_id` | PK | Identitas program yang ditetapkan |
| `member_id` | FK -> `members.member_id` | Member penerima |
| `program_id` | FK -> `workout_programs.program_id` | Program latihan |
| `trainer_id` | FK nullable -> `personal_trainers.trainer_id` | PT pendamping opsional |
| `assigned_date` | date | Tanggal program diberikan |
| `start_date` | date | Mulai program |
| `end_date` | date, nullable | Akhir program |
| `progress_percentage` | decimal(5,2) | 0.00–100.00 |
| `program_status` | enum | `active`, `completed`, `stopped` |
| `trainer_notes` | text, nullable | Catatan trainer |
| `created_at` | timestamp | Audit |
| `updated_at` | timestamp | Audit |

### 5.11 `member_exercise_checks`

Checklist sederhana untuk validasi gerakan oleh PT. Tidak menyimpan beban atau repetisi aktual.

| Field | Tipe/aturan | Keterangan |
|---|---|---|
| `check_id` | PK | Identitas validasi |
| `member_program_id` | FK | Assignment program member |
| `program_exercise_id` | FK | Jadwal exercise yang divalidasi |
| `validated_by` | FK -> `personal_trainers.trainer_id` | PT yang mendampingi |
| `validated_at` | timestamp | Waktu validasi |
| `notes` | text, nullable | Catatan singkat |

## 6. Relasi Data

```text
users
 ├── members
 └── personal_trainers

members
 ├── membership_subscriptions
 ├── payment_transactions
 └── member_programs

membership_packages
 └── membership_subscriptions

personal_trainers
 ├── workout_programs
 └── member_programs

workout_programs
 └── program_exercises
      └── exercises
```

## 7. Aturan Status Membership

Status di UI sebaiknya dihitung dari data aktual, bukan hanya mengandalkan label statis.

| Kondisi | Status yang ditampilkan |
|---|---|
| Ada subscription `active`, pembayaran terkait `paid`, dan hari ini berada di antara `start_date`–`end_date` | `Aktif` |
| Masih aktif tetapi sisa masa berlaku <= 7 hari | `Aktif — segera berakhir` |
| Hari ini melewati `end_date` | `Kedaluwarsa` |
| Tidak punya subscription valid | `Tidak aktif` |
| Subscription `cancelled` | `Dibatalkan` |

Ketika halaman dibuka, sistem harus tetap memperlakukan membership yang melewati `end_date` sebagai kedaluwarsa walaupun proses pembaruan status database belum berjalan. Boleh gunakan scheduled job untuk memperbarui nilai `subscription_status` menjadi `expired`, tetapi perhitungan saat membaca data tetap wajib benar.

## 8. Halaman Minimum

### 8.1 Publik

- Login

### 8.2 Admin

- Dashboard
- Daftar member
- Detail/tambah/edit member
- Paket membership
- Pendaftaran dan perpanjangan membership
- Transaksi pembayaran dan verifikasi pembayaran
- Laporan pemasukan
- Daftar personal trainer
- Profil

### 8.3 Member

- Dashboard dengan digital membership card
- Detail membership
- Program latihan saya
- Riwayat pembayaran
- Profil

### 8.4 Personal Trainer

- Dashboard
- Daftar member binaan
- Katalog program latihan
- Daftar member binaan
- Checklist validasi gerakan member
- Profil

## 9. Prioritas Implementasi

### Fase 1 — Fondasi

- Konfigurasi aplikasi, database, migration/schema, seed data.
- Login, logout, role-based authorization.
- Layout mobile-first dan sistem desain dasar.

### Fase 2 — Operasional Membership

- CRUD admin untuk member, trainer, dan paket.
- Pendaftaran baru dan perpanjangan membership.
- Pembayaran serta verifikasi manual.
- Digital membership card dan status membership yang benar.

### Fase 3 — Program Latihan

- CRUD exercise.
- CRUD workout program dan program exercises.
- Assign program kepada member.
- Tampilan program latihan untuk member.

### Fase 4 — Laporan dan Penyempurnaan

- Ringkasan pendapatan bulanan dan daftar transaksi.
- Pencarian, filter, empty state, loading state, error state.
- Validasi, keamanan akses, dan pengujian alur per role.
- Penyempurnaan tampilan desktop tanpa mengorbankan mobile.

## 10. Kriteria Penerimaan Inti

- Member dapat login dan langsung melihat kartu dengan status membership yang benar.
- Admin dapat menambah member, memilih paket, mencatat pembayaran, serta memperpanjang masa aktif tanpa menghapus riwayat sebelumnya.
- Pembayaran `pending` tidak masuk laporan pemasukan dan tidak boleh dianggap sebagai membership aktif.
- Personal trainer dapat membuat program latihan dan menugaskannya kepada member.
- Member tidak bisa melihat data member lain.
- Tidak ada fitur attendance/check-in di database maupun UI MVP.
- UI utama tetap nyaman digunakan pada lebar layar ponsel 360–430 px.
