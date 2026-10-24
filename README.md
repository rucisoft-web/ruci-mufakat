# Ruci MUFAKAT — Board Management & AI

Ruang kerja digital untuk **Dewan Komisaris dan Direksi**: dari rencana rapat, board book berversi, ruang baca, kuorum, keputusan per organ, risalah, sampai tindak lanjut yang selesai hanya bila buktinya diverifikasi. Dilengkapi asisten AI yang selalu menunjuk dokumen, versi, dan halaman sumber.

**Mengapa "MUFAKAT"?** Diambil dari tradisi *musyawarah untuk mufakat*: keputusan dewan lahir dari pembahasan yang terdokumentasi. Hurufnya sekaligus merangkum alur kerja: **M**usyawarah · **U**sulan · **F**orum · **A**genda · **K**eputusan · **A**kuntabilitas · **T**indak lanjut. Alternatif nama yang dipertimbangkan: *Ruci SIDANG*, *Ruci DEWAN*, *Ruci KEMUDI*.

Dibangun dengan **Laravel 13, Filament 5, dan MySQL 8**. Siap dipasang di hosting cPanel biasa: tanpa Redis dan tanpa worker antrean (`QUEUE_CONNECTION=sync`). Lihat [PANDUAN-CPANEL.md](PANDUAN-CPANEL.md).

## Modul

| Kelompok | Isi |
|---|---|
| Beranda & dasbor | Beranda Saya (agenda, bahan belum dibaca, tugas, tanda tangan), Dasbor Pimpinan (KPI, grafik, peta panas, risiko teratas), Manfaat bagi Pimpinan |
| Rapat | Kalender dan jadwal, agenda dengan presenter/PIC, peserta (RSVP terpisah dari **kehadiran aktual**), kuorum per organ, board book berversi dengan daftar perubahan dan sidik SHA-256, rapat gabungan dengan suara per organ, benturan kepentingan per agenda |
| Dokumen | Pustaka dokumen (klasifikasi, versi, ekstraksi teks PDF), Ruang Baca (cari, sorotan, anotasi pribadi, status sudah dibaca) |
| Keputusan & tindak lanjut | Register keputusan dengan rantai sumber → rapat → keputusan → tugas → bukti, keputusan sirkuler, risalah (draf → reviu → tanda tangan → final, koreksi lewat addendum), tindak lanjut bertonggak dengan verifikasi bukti, permintaan informasi ber-SLA |
| Pengawasan | Kinerja keuangan (rasio dihitung aplikasi), kepatuhan rapat terhadap POJK 33/2014 (frekuensi, ketepatan bahan, kehadiran), register risiko, kepatuhan & temuan, evaluasi dewan, pengembangan dewan |
| Tata kelola | Perusahaan, organ & komite, keanggotaan dan masa jabatan, direktori pejabat historis, kebijakan, matriks kewenangan, aturan rapat (konfigurasi, bukan kode), RKAP/program kerja, pernyataan tahunan |
| Asisten AI | 12 fitur: ringkasan sebelum rapat, tanya jawab berbukti, pertanyaan kritis Komisaris, draf agenda, draf risalah, ekstraksi calon tugas, memori keputusan, perbandingan dokumen, pemeriksaan kelengkapan usulan, penjelasan kinerja, penerjemahan, draf laporan pengawasan |
| Sistem | Pengguna & peran, riwayat AI, jejak audit (termasuk akses yang ditolak), MFA aplikasi autentikator |

## Aturan proses yang dijaga aplikasi

- RSVP bukan bukti kuorum; kuorum dihitung dari kehadiran aktual anggota bersuara, dikurangi yang mundur karena benturan kepentingan pada agenda itu.
- Rapat gabungan menyimpan keputusan dan suara **per organ** (Direksi memutuskan, Dewan Komisaris menyetujui/menasihati).
- Risalah final tidak bisa diubah atau dihapus (dijaga di tingkat model); koreksi dibuat sebagai addendum yang tertaut.
- Tugas tidak bisa ditutup oleh PIC sendiri; selesai hanya setelah verifikator menerima bukti. Penolakan dan pembukaan kembali tercatat.
- Board book versi baru menggantikan versi lama dan mencatat apa yang berubah.
- Ketentuan POJK 33/2014 (frekuensi rapat, batas bahan H-5, kehadiran) disimpan sebagai **aturan rapat** yang bisa diubah, bukan ditanam di kode.
- Hak akses mengikuti organ, komite, masa jabatan, rapat, agenda, dan klasifikasi dokumen. Administrator teknis tidak otomatis membaca isi dokumen dewan. Presenter tamu hanya membuka agendanya sampai batas waktu.

## Prinsip asisten AI

- Hanya potongan halaman dari dokumen yang **boleh dibaca pengguna** yang dikirim ke OpenAI (Responses API, `store: false`, skema JSON ketat).
- Setiap pernyataan menyebut dokumen, versi, dan halaman. Rujukan yang tidak ada di sumber dibuang aplikasi.
- Fakta, hitungan aplikasi, dan analisis AI diberi label berbeda. Rasio dan pertumbuhan dihitung aplikasi secara deterministik.
- Informasi yang belum tersedia disebutkan; bila tidak ada bukti, AI tidak dipanggil sama sekali.
- AI tidak menentukan kuorum, memberi suara, menandatangani, atau mengesahkan. Semua keluaran berstatus **draf**.
- Tanpa `OPENAI_API_KEY`, aplikasi menampilkan keluaran referensi demo (berlabel jelas) sehingga tetap bisa diperagakan.

## Data demo (label DATA DEMO)

- **Fakta laporan**: profil dan angka keuangan PT Kimia Farma Tbk mengacu pada Laporan Tahunan 2025 sesuai spesifikasi (misalnya penjualan Rp9.223.328 juta vs Rp9.938.958 juta, rugi Rp(443.357) juta vs Rp(1.208.173) juta). Dokumen sumber di aplikasi berisi **halaman kutipan** yang memuat angka tersebut, bukan salinan lengkap laporan tahunan.
- **Hitungan aplikasi**: misalnya penjualan −7,20% dan rugi turun 63,30%.
- **Klarifikasi**: angka rasio lancar "56,37 x" di hlm. 19 ditandai *perlu klarifikasi* (hitungan aplikasi 0,56 kali).
- **Simulasi**: rapat, keputusan, pendapat, benturan kepentingan, tugas, dan keterlambatan memakai alias "Demo …" dan bukan tindakan pejabat sebenarnya. Nama pejabat sebenarnya hanya ada di *Direktori Pejabat Historis* dan perlu diverifikasi ulang terhadap sumber resmi sebelum dipakai di luar demo.

### Akun demo (kata sandi `Mufakat#2026`)

| Peran | Email |
|---|---|
| Komisaris Utama / Independen | komut@mufakat.demo |
| Komisaris Independen, Ketua Komite Audit | kom.independen@mufakat.demo |
| Komisaris (Ketua KNR / Pemantau Risiko / anggota) | komisaris.a, komisaris.b, komisaris.c @mufakat.demo |
| Direktur Utama | dirut@mufakat.demo |
| Direktur Keuangan & MR, Produksi, Komersial, SDM, Portofolio | dirkeu, dirprod, dirkom, dirsdm, dirport @mufakat.demo |
| Anggota Komite Audit / Risiko | komite.audit1, komite.audit2, komite.risiko @mufakat.demo |
| Sekretaris Perusahaan | sekper@mufakat.demo |
| Sekretaris Dewan Komisaris | sekdekom@mufakat.demo |
| PIC tindak lanjut | vp.akuntansi, vp.treasury, kepala.spi, vp.scm, vp.aset, vp.ritel @mufakat.demo |
| Auditor | auditor@mufakat.demo |
| Administrator teknis | admin@mufakat.demo |
| Presenter tamu | konsultan@mufakat.demo |

Tombol **Masuk sebagai tamu** di halaman login membuka seluruh data demo dalam mode hanya-lihat. Skenario peragaan 30–40 menit ada di menu **Panduan Demo**. Halaman publik untuk pimpinan: `/untuk-pimpinan`.

## Menjalankan secara lokal

```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* (MySQL 8), lalu:
MUFAKAT_DEMO=true php artisan migrate --seed
php artisan serve        # buka http://localhost:8000
```

Perintah aplikasi:

| Perintah | Fungsi |
|---|---|
| `php artisan mufakat:doctor` | Memeriksa kesiapan server dan konfigurasi |
| `php artisan mufakat:sirkuler` | Menutup keputusan sirkuler yang melewati batas waktu (terjadwal tiap jam) |
| `php artisan mufakat:pengingat` | Pengingat tugas terlambat kepada PIC (terjadwal 07.00) |
| `php artisan db:seed --class=DemoSeeder` | Memuat ulang data demo |

## Pengujian

Uji memakai database MySQL `bm_test` (lihat `phpunit.xml`):

```bash
php artisan test
```

25 uji (467 pemeriksaan) mencakup hak akses ±40 halaman × 12 peran dan aturan proses: kuorum vs RSVP, benturan kepentingan, pemisahan organ di rapat gabungan, risalah final + addendum, verifikasi bukti tugas, sirkuler, rasio, AI tanpa bukti, pembuangan rujukan palsu, penolakan akses dokumen remunerasi, batas agenda presenter tamu, dan board book v2.

---
© 2026 Rucisoft · Ruci MUFAKAT
