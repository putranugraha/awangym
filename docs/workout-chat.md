# Asisten latihan Awan Gym

Urutan sumber pengetahuan:

1. **Database web** untuk fakta program resmi gym: nama, tingkat, tujuan,
   durasi, jadwal, gerakan, set/repetisi dan waktu istirahat yang tersimpan.
2. **PDF pendukung** untuk prinsip/teknik umum serta program buku yang
   disebut secara eksplisit: `The Worlds Fittest Book.pdf` dan
   `Program_Latihan_12_Minggu_yUKVCpR.pdf`.

Database dibaca saat pertanyaan, tanpa snapshot durasi atau hardcode angka.
Contoh data aktif saat pemeriksaan: Gym Beginner 8 minggu dan Gym Strength
12 minggu. Angka ini adalah milik program web; judul program PDF 12 minggu
bukan alasan mengganti durasi lokal menjadi 12 minggu.

## Fakta katalog dan pemilihan program

`WorkoutCatalogKnowledge` hanya membaca program aktif dan field latihan.
Tidak ada pembacaan member lain, pembayaran, akun pribadi, atau catatan
trainer. Alias beginner/pemula, strength/kekuatan, nama/kode program, dan
riwayat pertanyaan pengguna membantu mengenali program yang dimaksud.
Program yang disebut pada pertanyaan sekarang didahulukan dari riwayat.
Jika nama ambigu/tidak ditemukan, chatbot meminta klarifikasi.

Pertanyaan durasi sederhana seperti "program beginner pada gym ini berapa
minggu" dijawab langsung dari `duration_weeks` di database. Tidak ada
panggilan AI atau pembacaan PDF untuk mengganti nilainya; perubahan durasi
di database langsung terbaca pada pertanyaan berikutnya.

Untuk pertanyaan gabungan/jadwal, sumber database dipasang lebih dulu dengan
`source_type=database` dan `priority=100`, bersama fakta program terstruktur.
Jadwal diambil untuk minggu yang diminta (default minggu pertama), dengan
jumlah record dibatasi. AI dan pemeriksa makna diwajibkan memakai field DB
untuk program gym, tidak menambahkan fase atau gerakan PDF yang tidak
tercatat. Riwayat jawaban asisten lama tidak menjadi otoritas.

Pertanyaan yang secara eksplisit meminta program buku/Fittest/Ross/upper-
lower 4 hari memakai sumber PDF yang diminta. Perbandingan gym dengan buku
harus membedakan masing-masing program. Katalog umum tidak membuktikan
program yang ditugaskan secara pribadi ke seorang member.

## Perilaku pengguna

- Tombol Tanya Program berada di kanan bawah halaman setelah login.
- Banner "Teman ngobrol soal latihan · The World’s Fittest Book" dihapus.
  Status proses menampilkan `thinking...`.
- Respons sederhana berupa paragraf pendek. Rekomendasi/jadwal dapat memakai
  daftar poin/nomor dan label tebal agar mudah dipindai. Renderer memakai DOM
  dengan textContent, bukan innerHTML; HTML dan tautan tetap teks.
- Jawaban memakai bahasa Indonesia santai (aku/kamu), umumnya 60–100 kata.
  Batas server 180 kata memberi ruang untuk rekomendasi dan satu pertanyaan
  lanjutan tanpa memotong klaim di tengah kalimat.
- Setiap jawaban asisten diakhiri emoji. Tidak ada kutipan, nomor halaman,
  daftar sumber, atau rincian bukti yang dikirim ke UI.
- Percakapan tetap hanya tentang latihan: bodyweight, strength, speed/power,
  teknik gerakan, progresi, recovery, lari, marathon, rowing dan jadwal.
  Tujuan turun berat badan, ukuran tubuh, keterbatasan gerak, waktu dan alat
  diterima sebagai konteks latihan. Diagnosis, obat, diet rinci dan topik di
  luar latihan tetap dibatasi. Materi ambigu tidak diisi dari internet.

## Indeks sumber privat

`resources/knowledge/worlds-fittest-book.json` menyimpan indeks 73 halaman
buku pertama. `resources/knowledge/program-latihan-12-minggu.json` menyimpan
23 halaman PDF tambahan, termasuk 10 bagian teks materi/jadwal. Keduanya
memiliki fingerprint SHA-256, teks asli, metadata dokumen dan fase. Halaman
ilustrasi dengan label saja dicatat tetapi tidak dipakai untuk mengarang
instruksi gerakan yang tidak tertulis. Nomor halaman hanya
untuk audit internal. File ini berada di resources, tidak di public, dan tidak
perlu dikirim utuh ke browser atau API.

PDF pertama adalah halaman pilihan dari buku, bukan seluruh buku lengkap berurutan.
Seluruh halaman tercatat dalam indeks. Ada 58 bagian teks latihan yang dapat
dicari; halaman diet/medis, sampul, serta gambar yang hanya memiliki judul
tidak menjadi bukti jawaban latihan. Ilustrasi tanpa penjelasan teks tidak
secara otomatis diterjemahkan menjadi instruksi baru.

Ringkasan kolom jadwal bodyweight dan strength ditranskripsikan sesuai
pengecekan visual PDF agar kolom fase tidak tertukar. Teks aslinya tetap ada.
Bagian yang bertentangan diberi catatan, antara lain istirahat The Mountain,
persentase zona speed/strength, serta fase Olympic hybrid. Model tidak boleh
memilih angka pasti sepihak pada bagian yang ambigu.

Untuk memperbarui indeks setelah mengganti PDF:

```sh
python -m pip install -r scripts/requirements-book.txt
python scripts/build_workout_book.py
```

Perintah pembangunan ulang memproses kedua PDF. Periksa ulang tabel dan
catatan jika salah satu PDF berubah; pemetaan fase/halaman mengikuti versi
dokumen yang diberikan saat ini. Python/pypdf hanya diperlukan
untuk pembangunan ulang indeks, bukan saat melayani request web.

## Provider AI

Google AI Studio / Gemini:

```dotenv
WORKOUT_CHAT_PROVIDER=gemini
GEMINI_API_KEY=key_server_Anda
GEMINI_MODEL=gemini-3.5-flash-lite
```

OpenAI tetap didukung melalui `WORKOUT_CHAT_PROVIDER=openai`,
`OPENAI_API_KEY`, dan `WORKOUT_CHAT_MODEL`. Setelah konfigurasi berubah,
jalankan `php artisan config:clear` selama development atau bangun ulang
cache konfigurasi saat deployment. Secret tidak boleh memakai awalan VITE_.
Jawaban generatif memerlukan API key dan indeks PDF; kegagalan provider
atau indeks menghasilkan pesan aman. Jawaban durasi sederhana dapat
dilayani langsung dari database tanpa key/API.

## Pemeriksaan internal

Retriever mencari bagian buku menggunakan kata kunci, padanan Indonesia–Inggris,
riwayat pertanyaan, dan fase minggu. Biasanya delapan bagian dikirim; bagian
terkait ditambahkan bila diperlukan untuk konteks teknik atau kontradiksi.
Identitas dokumen selalu diteruskan ke AI. Pertanyaan upper/lower split 4
hari, RPE, atau Program Latihan 12 Minggu memprioritaskan sumber tambahan;
pertanyaan Fittest/Ross memprioritaskan sumber pertama. Parameter dua
program tidak boleh dicampur. Parser minggu menghindari salah membaca
"program 12 minggu 4 hari" sebagai permintaan minggu 4.

AI menyusun jawaban natural dan ID beberapa sumber sebagai metadata internal.
Kutipan persis per paragraf dan pemeriksaan bahwa semua angka harus muncul
dalam kutipan sudah dihapus. Tinggi/berat/usia atau durasi yang disebut
pengguna adalah konteks pribadi, bukan fakta yang harus ada dalam buku.

Rekomendasi boleh menggabungkan beberapa bagian buku dan memilih/mengurangi
latihan sesuai tujuan, pengalaman, alat serta waktu. Usulan penyesuaian harus
jelas sebagai saran, tidak diklaim sebagai program asli buku. Angka dari
program asli tetap harus sesuai fase/tabel. AI tidak boleh menghitung BMI,
menetapkan beban kg dari ukuran tubuh, menjanjikan hasil, atau menganggap
kursi rumah pasti setara dengan bench/box olahraga.

Server memeriksa ID sumber, panjang jawaban, HTML, URL, serta sitasi yang tidak
diminta. Panggilan AI kedua memeriksa makna dengan membedakan fakta buku,
data pengguna, dan usulan adaptasi yang wajar. Klaim palsu, profil palsu,
volume berlebihan dan diagnosis tetap tidak boleh lolos. Jika suatu saran
belum dapat diterima, pertanyaan personal mendapat pertanyaan lanjutan
berdasarkan informasi yang belum diketahui, bukan penolakan bahwa buku tidak
memuat tinggi/berat pengguna. Pertanyaan yang menyebut diet atau rasa tidak
nyaman tidak otomatis ditolak jika maksudnya masih konsultasi latihan.

Tidak ada bukti atau nomor halaman yang dikirim ke UI. Pemeriksaan semantik
berbasis model mengurangi risiko halusinasi, namun bukan jaminan matematis
bahwa setiap parafrasa atau saran adaptasi selalu benar.

Tidak ada pencarian internet, scraping, tool eksternal, akses data member lain,
atau pengambilan konten tautan video. OpenAI memakai store=false. Kedua
provider menerima pertanyaan, maksimal delapan pertukaran percakapan terakhir dari
database milik akun yang login, fakta katalog bersama kutipan buku yang relevan. History dari client diabaikan.
Session `workout_chat.book_turns` tetap menyimpan konteks percakapan terakhir;
fakta program yang tercatat di database selalu mengalahkan jawaban lama.

Endpoint tetap memerlukan auth/verified, CSRF, maksimal input 1.000 karakter,
dan throttle 12 request per menit per user. Jawaban normal memerlukan dua
panggilan AI, termasuk klarifikasi dinamis yang diperiksa maknanya.
Client menunggu hingga 60 detik.

## Validasi

Penggantian alat rumah juga diperiksa: saran step-up/naik atau melompat ke
kursi tidak langsung diteruskan. Chatbot mengarahkan percakapan ke pilihan
gerakan tanpa pijakan kursi, tanpa menolak konsultasi secara keseluruhan.

Test meliputi fingerprint/cakupan indeks, progresi strength asli buku,
retrieval snatch/marathon, konflik buku, ketiadaan sumber website, respons
singkat dengan emoji tanpa metadata bukti, konteks percakapan baru, penolakan
klaim latihan atau ID palsu, HTML, nomor halaman, topik luar latihan, dan kegagalan
provider/indeks/key. Pengujian langsung Gemini berhasil pada bench press fase
awal dan teknik snatch; pertanyaan angka konflik tidak menghasilkan tebakan.
Build, Pint, dan analisis statis dijalankan. Review dilakukan langsung pada
kode; pemeriksaan visual browser belum dilakukan karena izin browser ke
localhost sebelumnya ditolak.


Perubahan fleksibilitas diuji pada pertanyaan "aku tinggi 160cm dengan berat
90kg, ada rekomendasi latihan untukku?", konteks pemula dengan tujuan turun
berat badan, waktu 20 menit di rumah, dan alat terbatas. Test juga memastikan
ukuran tubuh tidak diblokir karena tidak ada dalam buku, beberapa sumber boleh
digabung tanpa kutipan, dan klarifikasi tidak menanyakan kembali pengalaman,
tujuan, tempat, atau waktu yang sudah diberikan.


Validasi tambahan mencakup fingerprint 23 halaman, retrieval fase minggu
1–4/5–8/9–12, penggunaan kedua sumber, dan renderer daftar/tebal yang aman.
Pengujian langsung Gemini mengembalikan dumbbell bench press program 4 hari:
fase 1–4 sebanyak 3 set, 10–12 reps, RPE 7–8, rest 60–90 detik; fase 9–12
sebanyak 4 set, 6–8 reps, RPE 9, rest 90–120 detik. Penjelasan RPE juga
berhasil memakai glossary PDF tambahan. Tidak ada kutipan/nomor halaman yang
ditampilkan pada jawaban pengguna. Pemeriksaan visual browser tetap belum
dijalankan karena akses localhost sebelumnya ditolak; pengujian format
mencakup parser teks dan pemeriksaan kode secara langsung.


Koreksi prioritas database diuji dengan kasus Gym Beginner 8 minggu,
perubahan durasi menjadi 10 minggu di database pengujian, pertanyaan lanjutan
setelah asisten keliru menyebut 12 minggu, pergantian nama program, konflik
angka latihan PDF/DB, program ambigu/nonaktif, serta pertanyaan eksplisit
buku yang tetap memakai PDF. Pengujian langsung database lokal dan Gemini
berhasil membedakan Gym Beginner 8 minggu dari program bodyweight buku
12 minggu. Tidak ada data program yang diubah pada database lokal.


## Memory per akun

Percakapan tersimpan pada `workout_chat_turns`, dengan foreign key `user_id`
ke `users.user_id`. Riwayat tetap tersedia setelah reload atau login ulang.
Pemilik selalu diambil dari autentikasi server; `user_id` dan history dari
client maupun session lama tidak dipakai. Tidak ada penyimpanan chat di
localStorage browser. Menghapus akun juga menghapus percakapannya.

Saat panel dibuka, endpoint GET `/workout-chat/history` memuat 12 pertukaran
terakhir milik akun tersebut dengan `Cache-Control: no-store`. AI menerima
delapan pertukaran terakhir; seluruh percakapan yang berhasil tetap disimpan
di database. Ini merupakan konteks percakapan terbatas, bukan ingatan tanpa
batas atas seluruh riwayat. Respons gagal dari provider tidak disimpan.
Riwayat lama yang hanya ada di session tidak diimpor karena kepemilikan
akun asalnya tidak dapat dipastikan.
