# Panduan Instalasi Ruci MUFAKAT di cPanel

Untuk hosting cPanel biasa (shared hosting atau VPS dengan cPanel). Ruci MUFAKAT **tidak memerlukan Redis, worker antrean, atau Node.js**: semua proses berjalan langsung saat diminta.

Contoh di panduan memakai akun cPanel `userku`, folder program `/home/userku/ruci-mufakat`, dan alamat `https://mufakat.domainanda.com`. Sesuaikan dengan milik Anda.

## 0. Cara tercepat: paket siap-upload (tanpa Terminal, tanpa edit file)

Isi zip berada di dalam satu folder **`ruci-mufakat`**. Setelah diekstrak, folder itu yang menjadi lokasi program, dan domain/subdomain diarahkan ke subfolder **`ruci-mufakat/public`**.

```
/home/userku/
├── public_html/          ← situs utama Anda (tidak disentuh)
└── ruci-mufakat/         ← hasil ekstrak zip (program, .env, storage, vendor)
    └── public/           ← document root subdomain mufakat.domainanda.com
```

1. Pastikan PHP **8.3 atau lebih baru** (*MultiPHP Manager* atau *Select PHP Version*).
2. Buat database di cPanel › **MySQL Databases**: database, user, lalu *Add User To Database* dengan **ALL PRIVILEGES**. Catat nama database, user, dan kata sandinya.
3. *File Manager* › buka folder **home** (`/home/userku`, satu tingkat di atas `public_html`) › **Upload** `ruci-mufakat-siap-upload.zip`.
4. Klik kanan zip › **Extract** › biarkan tujuannya `/home/userku` › *Extract Files*. Terbentuk folder `/home/userku/ruci-mufakat`. Hapus zip-nya.
5. cPanel › **Domains** › *Create A New Domain*: isi `mufakat.domainanda.com`, **matikan** *Share document root*, lalu isi **Document Root** dengan `ruci-mufakat/public`. Simpan, lalu aktifkan SSL (*SSL/TLS Status* › Run AutoSSL).
   - Domain/subdomain yang sudah ada: *Domains* › *Manage* › ubah *Document Root* menjadi `ruci-mufakat/public`.
   - cPanel lama: *Subdomains* › isi subdomain, *Document Root* `ruci-mufakat/public`.
6. PHP versi subdomain itu juga harus 8.3 (*MultiPHP Manager*).
7. Buka `https://mufakat.domainanda.com`. Halaman **Instalasi Ruci MUFAKAT** muncul otomatis, menampilkan lokasi program, dan memeriksa server. Semua harus ✓.
8. Isi alamat situs, data database, nama/email/kata sandi administrator. Centang:
   - **Muat data demo Kimia Farma** untuk situs promosi (mengaktifkan tombol *Masuk sebagai tamu*);
   - **Wajibkan MFA** untuk pemakaian sungguhan;
   - isi **kunci OpenAI** bila ingin AI sungguhan (boleh dikosongkan).
9. Klik **Pasang sekarang** (sekitar 1 menit bila memuat data demo). Halaman selesai menampilkan alamat aplikasi dan perintah cron.

Halaman instalasi otomatis mati setelah berhasil (`MUFAKAT_INSTALLER=false`) dan kunci aplikasi baru dibuat.

> **Jangan** membuka aplikasi sebagai subfolder, misalnya `domainanda.com/ruci-mufakat`. Laravel harus berada di akar sebuah domain atau subdomain. Bila zip terlanjur diekstrak di dalam `public_html` (menjadi `public_html/ruci-mufakat`), cukup arahkan document root subdomain ke `public_html/ruci-mufakat/public`. File `.htaccess` di folder program tetap menolak akses ke `.env`, `storage`, dan `vendor`, tetapi lokasi di luar `public_html` lebih aman.

Langkah 1–7 di bawah adalah cara manual, tidak diperlukan bila memakai paket siap-upload.

## 1. Prasyarat hosting

| Komponen | Ketentuan | Tempat di cPanel |
|---|---|---|
| PHP | **8.3 atau lebih baru** | *MultiPHP Manager* / *Select PHP Version* |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`, `openssl`, `bcmath`, `ctype`, `tokenizer`, `xml`, `dom`, `curl`, `gd` | *Select PHP Version › Extensions* |
| Database | **MySQL 8** atau MariaDB 10.6+ (dipakai pencarian FULLTEXT) | *MySQL Databases* |
| Cron | Opsional tetapi disarankan (bagian 6) | *Cron Jobs* |
| SSL | Disarankan, wajib bila MFA dipakai | *SSL/TLS Status* › Run AutoSSL |
| Batas unggah | `upload_max_filesize` dan `post_max_size` minimal 50M | *MultiPHP INI Editor* |

Opsional: `pdftotext` (poppler-utils) membuat ekstraksi teks PDF lebih rapi. Tanpa itu aplikasi memakai pembaca PDF bawaan PHP; dokumen juga bisa diisi dengan menempelkan teks per halaman.

## 2. Taruh program di server (cara manual)

Paling aman program diletakkan **di luar `public_html`**:

1. Upload `ruci-mufakat-siap-upload.zip` ke `/home/userku` dan ekstrak di sana; folder `ruci-mufakat` terbentuk otomatis.
2. *Domains* › **Create A New Domain** (misalnya `mufakat.domainanda.com`), matikan *Share document root*, isi **Document Root**: `ruci-mufakat/public`.
3. Aktifkan SSL untuk domain itu.

Dengan Terminal, alternatifnya `git clone` repo lalu `composer install --no-dev --optimize-autoloader`.

## 3. Isi file `.env`

Paket sudah berisi `.env` siap-instal. Untuk instalasi manual, salin `.env.example` menjadi `.env`, lalu sesuaikan:

```dotenv
APP_URL=https://mufakat.domainanda.com
DB_DATABASE=userku_mufakat
DB_USERNAME=userku_mufakat
DB_PASSWORD="kata-sandi-database"

MUFAKAT_INSTALLER=false
MUFAKAT_MFA_REQUIRED=true
MUFAKAT_ADMIN_EMAIL=admin@domainanda.com
MUFAKAT_ADMIN_PASSWORD="SandiSementara#2026"
MUFAKAT_DEMO=false          # true untuk memuat data demo Kimia Farma
MUFAKAT_DEMO_GUEST=false    # true untuk tombol "Masuk sebagai tamu"

OPENAI_API_KEY=             # opsional
OPENAI_MODEL=gpt-5-mini
```

Kata sandi yang mengandung `#`, `$`, atau spasi harus diberi tanda kutip.

## 4. Kunci aplikasi, tabel, dan admin

Dengan Terminal:

```bash
cd ~/ruci-mufakat
php artisan key:generate --force
php artisan migrate --seed --force
php artisan optimize
php artisan mufakat:doctor
```

Tanpa Terminal, tambahkan perintah berikut satu per satu di **Cron Jobs** (*Once Per Minute*), tunggu 1–2 menit, periksa hasilnya, lalu **hapus** cron itu sebelum menambah yang berikutnya:

1. `cd /home/userku/ruci-mufakat && php artisan key:generate --force > storage/logs/setup-1.txt 2>&1`
2. `cd /home/userku/ruci-mufakat && php artisan migrate --seed --force > storage/logs/setup-2.txt 2>&1`
3. `cd /home/userku/ruci-mufakat && php artisan optimize > storage/logs/setup-3.txt 2>&1`

Setelah berhasil, kosongkan `MUFAKAT_ADMIN_PASSWORD` di `.env`.

## 5. Mulai memakai

1. Buka `https://mufakat.domainanda.com/app` dan masuk sebagai administrator.
2. **Tata Kelola › Perusahaan, Organ & Komite, Keanggotaan**: isi struktur dewan dan masa jabatan.
3. **Pengguna**: buat akun dan tetapkan peran (Komisaris, Direksi, Komite, Sekretaris Perusahaan, Sekretaris Dewan Komisaris, PIC, Auditor, Tamu/Presenter).
4. **Aturan Rapat**: periksa nilai bawaan POJK 33/2014 dan sesuaikan dengan anggaran dasar/pedoman kerja.
5. Sekretariat mulai menjadwalkan rapat, menyusun agenda, dan menerbitkan board book.

Administrator teknis mengelola akun dan konfigurasi, tetapi **tidak otomatis membaca** isi rapat dan dokumen dewan.

## 6. Cron (disarankan)

Satu cron, jadwal *Once Per Minute*, dibiarkan selamanya:

```
cd /home/userku/ruci-mufakat && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Cron menutup keputusan sirkuler yang lewat batas waktu (tiap jam) dan mengirim pengingat tugas terlambat (07.00). Tanpa cron aplikasi tetap berjalan: status sirkuler diperbarui saat halamannya dibuka. Bila PHP 8.3 hosting ada di lokasi lain, pakai misalnya `/opt/cpanel/ea-php83/root/usr/bin/php`.

## 7. Asisten AI

- Isi `OPENAI_API_KEY` di `.env`, lalu `php artisan optimize`. Tanpa kunci, fitur lain tetap jalan dan asisten menampilkan keluaran referensi demo.
- Hanya potongan halaman dari dokumen yang boleh dibaca pengguna yang dikirim, dengan `store: false`. Batasnya diatur `MUFAKAT_AI_MAX_PAGES` dan `MUFAKAT_AI_MAX_CHARS`.
- Setiap keluaran tercatat di **Riwayat AI** dan berstatus draf.

## 8. Memperbarui program

Backup database dan folder `storage/app` lebih dulu. Upload zip versi baru, ekstrak menimpa folder program **kecuali `.env`** (hapus `.env` dari zip sebelum diekstrak, atau simpan salinannya dan kembalikan), lalu jalankan `php artisan migrate --force` dan `php artisan optimize` (lewat Terminal atau cron sekali jalan).

## 9. Masalah umum

| Gejala | Solusi |
|---|---|
| Error 500 tanpa keterangan | Lihat `storage/logs/laravel-*.log`. Sementara boleh `APP_DEBUG=true`, lalu kembalikan ke `false`. |
| `Your PHP version … does not satisfy` | Ubah PHP domain ke 8.3; pastikan cron/Terminal juga memakai PHP 8.3. |
| `Specified key was too long` | Database bukan utf8mb4 atau MySQL terlalu lama. Pakai MySQL 8 / MariaDB 10.6+. |
| Halaman instalasi tidak bisa dilanjutkan | Aktifkan ekstensi yang bertanda ✗; izin folder `storage` dan `bootstrap/cache` 755 (atau 775) dan file `.env` 644. |
| Halaman tampil tanpa gaya | Document root belum ke folder `public`, atau `.htaccess` di akar paket tidak ikut terekstrak. |
| `419 Page Expired` saat login | `APP_URL` harus sama persis dengan alamat yang dibuka. Bila belum SSL, isi `SESSION_SECURE_COOKIE=false`. |
| Unggah dokumen gagal | Naikkan `upload_max_filesize`/`post_max_size`; aktifkan ekstensi **fileinfo** dan **zip**. |
| Teks PDF hasil pindai kosong | Dokumen pindaian tidak memiliki teks. Pakai PDF berteks atau tempel teks per halaman saat mengunggah. |
| Pengguna kehilangan ponsel MFA | Administrator › Pengguna › *Reset MFA*; pengguna memindai kode QR baru saat masuk. |
| Perubahan `.env` tidak berlaku | `php artisan optimize:clear` lalu `php artisan optimize`. |
