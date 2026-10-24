<?php

namespace Database\Seeders;

/**
 * Isi halaman dokumen demo Kimia Farma.
 *
 * Laporan Tahunan 2025: hanya cuplikan berisi angka/keterangan yang dikutip dalam spesifikasi
 * (bukan salinan laporan). Halaman yang rinciannya tidak dimuat ditandai jelas, sehingga AI
 * menyatakan "bukti belum tersedia" alih-alih mengarang.
 *
 * Dokumen rapat (laporan kinerja triwulan, RKAP 2027, kajian, dsb.) adalah SIMULASI.
 */
class KimiaFarmaDocuments
{
    public const NOTE = 'CUPLIKAN DEMO — disusun dari angka yang dikutip dari Laporan Tahunan 2025 PT Kimia Farma Tbk untuk keperluan demonstrasi. Verifikasi ke dokumen asli sebelum dipakai resmi.';

    /** @return array<int, array{0: string, 1: string}> nomor halaman => [judul, isi] */
    public static function annualReport(): array
    {
        $n = self::NOTE;

        return [
            8 => ['Restrukturisasi Keuangan', "{$n}\n\nSepanjang 2025 Perseroan melanjutkan program restrukturisasi keuangan dan reorientasi bisnis. Restrukturisasi utang dengan 11 bank kreditur dinyatakan selesai pada 16 Desember 2025. Rincian jadwal pembayaran pokok, bunga, dan persyaratan (covenant) per kreditur TIDAK dimuat dalam cuplikan demo ini."],
            16 => ['Ikhtisar Keuangan — Laba Rugi (dalam jutaan Rupiah)', "{$n}\n\nPos | 2025 | 2024\nPenjualan neto | 9.223.328 | 9.938.958\nLaba bruto | 3.063.410 | 2.946.473\nRugi usaha | (79.759) | (810.720)\nRugi tahun berjalan konsolidasian | (443.357) | (1.208.173)\n\nAngka dalam tanda kurung menunjukkan rugi."],
            17 => ['Ikhtisar Keuangan — Posisi Keuangan (dalam jutaan Rupiah)', "{$n}\n\nPos | 31 Des 2025 | 31 Des 2024\nAset lancar | 3.979.719 | 3.798.747\nLiabilitas jangka pendek | 7.060.554 | 7.910.053\nTotal aset | 14.183.178 | 14.928.544\nEkuitas | 2.735.649 | 3.428.809"],
            18 => ['Ikhtisar Keuangan — Arus Kas (dalam jutaan Rupiah)', "{$n}\n\nPos | 2025 | 2024\nArus kas bersih dari aktivitas operasi | 234.364 | 153.551\nKas dan setara kas akhir periode | 377.089 | 437.589"],
            19 => ['Ikhtisar Keuangan — Rasio', "{$n}\n\nTabel rasio keuangan pada halaman ini menampilkan Rasio Lancar 2025 sebesar 56,37 dengan label satuan x (kali).\n\nCatatan pemeriksaan kualitas data (aplikasi): aset lancar ÷ liabilitas jangka pendek = 3.979.719 ÷ 7.060.554 = 0,5637 kali atau 56,37%. Nilai sumber dipertahankan dan ditandai sebagai kebutuhan klarifikasi satuan. Rasio lain pada tabel ini tidak dimuat dalam cuplikan demo."],
            21 => ['Penjualan per Segmen 2025', "{$n}\n\nPenjualan neto 2025 per segmen usaha (pembulatan, Rp miliar): Distribusi sekitar 4.330; Ritel (apotek dan klinik) sekitar 3.360; Manufaktur sekitar 729, naik sekitar 33% dibanding 2024; Lainnya (laboratorium dan layanan lain) sekitar 804. Jumlah segmen setara dengan penjualan neto Rp9.223.328 juta. Rincian 2024 per segmen tidak dimuat dalam cuplikan demo."],
            22 => ['Jumlah Fasilitas Layanan', "{$n}\n\nJaringan kelompok usaha per akhir 2025: 9 pabrik, 45 cabang distribusi, 1.050 apotek, 386 klinik, dan 65 laboratorium klinik."],
            83 => ['Profil Perusahaan dan Entitas Anak', "{$n}\n\nPT Kimia Farma Tbk (kode saham KAEF) adalah perusahaan farmasi terintegrasi hulu–hilir: bahan baku, manufaktur, distribusi, ritel apotek, klinik, dan laboratorium. Entitas anak utama yang dipakai dalam demo: PT Kimia Farma Apotek (ritel apotek dan klinik), PT Kimia Farma Trading & Distribution (distribusi), PT Phapros Tbk (manufaktur), dan PT Kimia Farma Sungwun Pharmacopia (bahan baku obat). Persentase kepemilikan per entitas tidak dimuat dalam cuplikan demo."],
            409 => ['Susunan Dewan Komisaris', "{$n}\n\nSusunan Dewan Komisaris per 31 Desember 2025 (setelah RUPS Tahunan 30 Juli 2025 dan RUPS Luar Biasa 3 November 2025, menurut keterbukaan informasi dan pemberitaan): Stefan Looho (Komisaris Utama/Independen), Sumarjati Arjoso, Suprianto, Fachmi Idris, dan Diah Kusumawardani (Komisaris). Cocokkan status independen dan tanggal pengangkatan dengan laporan asli."],
            418 => ['Komite Pendukung Pengawasan', "{$n}\n\nDalam melaksanakan pengawasan, Dewan Komisaris dibantu komite-komite, antara lain Komite Audit. Rincian komite lain, keanggotaan, dan jumlah rapatnya tidak dimuat dalam cuplikan demo. Struktur Komite Nominasi dan Remunerasi serta Komite Pemantau Risiko di aplikasi demo adalah simulasi."],
            424 => ['Susunan Direksi', "{$n}\n\nSusunan Direksi per 31 Desember 2025 (menurut keterbukaan informasi dan pemberitaan RUPS 2025): Djagad Prakasa Dwialam (Direktur Utama), Willy Meridian (Direktur Keuangan dan Manajemen Risiko), Hadi Kardoko (Direktur Produksi dan Supply Chain), Hanadi Setiarto (Direktur Komersial), Disril Revolin Putra (Direktur Sumber Daya Manusia), dan Jasmine Karsono (Direktur Portofolio, Produk dan Layanan)."],
            458 => ['Kebijakan dan Jumlah Rapat Dewan Komisaris', "{$n}\n\nHalaman ini memuat kebijakan rapat Dewan Komisaris dan jumlah rapat sepanjang 2025. Angka jumlah rapat dan tingkat kehadiran per anggota TIDAK dimuat dalam cuplikan demo ini."],
            466 => ['Jumlah Rapat Direksi', "{$n}\n\nHalaman ini memuat jumlah rapat Direksi dan rapat gabungan sepanjang 2025. Angka rinci TIDAK dimuat dalam cuplikan demo ini."],
            488 => ['Susunan Komite Audit (1)', "{$n}\n\nKomite Audit diketuai oleh Komisaris Independen dan beranggotakan pihak independen yang memiliki keahlian akuntansi/keuangan, sesuai POJK 55/POJK.04/2015. Nama anggota tidak dimuat dalam cuplikan demo."],
            489 => ['Susunan Komite Audit (2)', "{$n}\n\nLanjutan profil dan masa tugas Komite Audit. Rincian tidak dimuat dalam cuplikan demo."],
        ];
    }

    /** Dokumen rapat simulasi: kunci => [judul, jenis, klasifikasi, organ, halaman[]] */
    public static function simulated(): array
    {
        $sim = 'DATA DEMO — SIMULASI. Angka 2026–2027 adalah rekaan untuk menggambarkan fitur, bukan data resmi Kimia Farma.';

        return [
            'kinerja_q3' => ['Laporan Kinerja Konsolidasian Triwulan III 2026', 'laporan_kinerja', 'rahasia', 'gabungan', [
                ['Ringkasan Eksekutif', "{$sim}\n\nPenjualan neto Januari–September 2026 Rp6.760 miliar, 96% dari anggaran periode berjalan (Rp7.040 miliar). Laba bruto Rp2.270 miliar (margin 33,6%). Rugi usaha menyempit menjadi Rp18 miliar dibanding rugi Rp61 miliar pada periode yang sama 2025. Rugi bersih Rp214 miliar, terutama karena beban bunga pinjaman pasca restrukturisasi."],
                ['Penjualan per Segmen', "{$sim}\n\nDistribusi Rp3.120 miliar (−2% yoy); Ritel Rp2.510 miliar (+1% yoy); Manufaktur Rp590 miliar (+9% yoy); Lainnya Rp540 miliar (−4% yoy). Penurunan distribusi disebabkan rasionalisasi prinsipal bermarjin rendah."],
                ['Likuiditas dan Kewajiban', "{$sim}\n\nKas akhir September 2026 Rp412 miliar. Arus kas operasi Jan–Sep Rp186 miliar. Pembayaran pokok pinjaman pasca restrukturisasi Rp150 miliar dijadwalkan pada Desember 2026. Rasio lancar 0,61 kali. Rincian covenant per kreditur disajikan pada dokumen Rencana Pemenuhan Kewajiban."],
                ['Utilisasi Pabrik dan Persediaan', "{$sim}\n\nUtilisasi rata-rata 9 pabrik 52% (target 65%). Hari persediaan konsolidasi 118 hari (target 100 hari). Rekonsiliasi persediaan konsinyasi di gudang KFTD masih berjalan."],
            ]],
            'kewajiban' => ['Rencana Pemenuhan Kewajiban Pasca Restrukturisasi 2026–2030', 'kertas_usulan', 'sangat_rahasia', 'gabungan', [
                ['Ringkasan', "{$sim}\n\nDokumen ini memetakan kewajiban pokok dan bunga pinjaman pasca restrukturisasi dengan 11 bank. Total pokok terutang (simulasi) Rp4.650 miliar dengan tenor sampai 2032."],
                ['Jadwal Pembayaran', "{$sim}\n\nPerkiraan pembayaran pokok: 2026 Rp150 miliar; 2027 Rp420 miliar; 2028 Rp610 miliar. Jadwal bunga per kreditur belum dilampirkan. Sumber pembayaran 2027 mengandalkan optimalisasi aset non-produktif sebesar Rp250 miliar."],
                ['Persyaratan (Covenant)', "{$sim}\n\nPersyaratan keuangan utama yang wajib dipantau: rasio utang terhadap EBITDA, rasio cakupan layanan utang (DSCR), dan larangan pembagian dividen sebelum syarat terpenuhi. Nilai ambang (threshold) dan headroom per kreditur BELUM dicantumkan dalam versi ini."],
            ]],
            'rkap_v1' => ['Usulan RKAP 2027', 'kertas_usulan', 'rahasia', 'gabungan', [
                ['Asumsi Utama', "{$sim}\n\nTarget penjualan neto 2027 Rp10.100 miliar (+9,5% terhadap prognosa 2026). Asumsi pertumbuhan pasar farmasi 6%, kurs Rp16.300/USD, kenaikan harga bahan baku 3%."],
                ['Rencana Investasi', "{$sim}\n\nBelanja modal (capex) Rp420 miliar: modernisasi lini produksi Rp210 miliar, digitalisasi apotek dan klinik Rp120 miliar, pemeliharaan Rp90 miliar."],
                ['Proyeksi Laba Rugi', "{$sim}\n\nLaba bruto Rp3.430 miliar; laba usaha Rp210 miliar; laba bersih Rp35 miliar (titik impas pertama sejak 2022)."],
            ]],
            'rkap_v2' => ['Usulan RKAP 2027', 'kertas_usulan', 'rahasia', 'gabungan', [
                ['Asumsi Utama', "{$sim}\n\nTarget penjualan neto 2027 Rp9.900 miliar (+7,3% terhadap prognosa 2026), diturunkan dari Rp10.100 miliar pada versi 1 setelah rasionalisasi prinsipal distribusi. Asumsi pertumbuhan pasar farmasi 6%, kurs Rp16.500/USD, kenaikan harga bahan baku 3%."],
                ['Rencana Investasi', "{$sim}\n\nBelanja modal (capex) Rp360 miliar: modernisasi lini produksi Rp170 miliar, digitalisasi apotek dan klinik Rp100 miliar, pemeliharaan Rp90 miliar. Capex modernisasi ditahap mengikuti kemajuan konsolidasi pabrik."],
                ['Proyeksi Laba Rugi', "{$sim}\n\nLaba bruto Rp3.370 miliar; laba usaha Rp190 miliar; laba bersih Rp12 miliar."],
                ['Analisis Sensitivitas dan Covenant', "{$sim}\n\nBARU di versi 2. Bila penjualan 5% di bawah target, laba usaha turun menjadi Rp40 miliar dan arus kas operasi tidak cukup menutup pembayaran pokok 2027 tanpa hasil optimalisasi aset. Ambang covenant per kreditur masih menunggu konfirmasi Direktorat Keuangan."],
            ]],
            'aset' => ['Kajian Optimalisasi Aset Non-Produktif', 'kajian', 'sangat_rahasia', 'gabungan', [
                ['Ringkasan', "{$sim}\n\nTujuh aset tanah dan bangunan non-produktif diusulkan untuk dijual atau dikerjasamakan. Nilai buku Rp180 miliar; target hasil Rp250 miliar pada 2027."],
                ['Daftar Aset dan Penilaian', "{$sim}\n\nPenilaian KJPP baru tersedia untuk 4 dari 7 aset. Dua aset memerlukan persetujuan pemegang saham. Calon mitra kerja sama untuk aset Jakarta Timur telah menyampaikan minat."],
            ]],
            'utilisasi' => ['Rencana Peningkatan Utilisasi Pabrik', 'kertas_usulan', 'rahasia', 'direksi', [
                ['Usulan', "{$sim}\n\nKonsolidasi produksi dari 9 menjadi 7 pabrik aktif dalam 18 bulan. Utilisasi ditargetkan naik dari 52% menjadi 70%. Penghematan biaya tetap Rp85 miliar per tahun mulai 2028."],
                ['Alternatif dan Risiko', "{$sim}\n\nAlternatif A: konsolidasi 2 pabrik (dipilih). Alternatif B: maklon untuk pihak ketiga tanpa konsolidasi. Risiko: gangguan pasokan selama transfer teknologi, persetujuan BPOM atas perubahan tempat produksi, dan dampak SDM."],
            ]],
            'boardmanual' => ['Board Manual PT Kimia Farma Tbk (versi demo)', 'kebijakan', 'internal', null, [
                ['Rapat Direksi dan Dewan Komisaris', "{$sim}\n\nRapat Direksi diadakan paling sedikit satu kali setiap bulan. Rapat Dewan Komisaris paling sedikit satu kali dalam dua bulan. Rapat gabungan paling sedikit satu kali dalam empat bulan (mengacu POJK 33/POJK.04/2014). Bahan rapat yang terjadwal disampaikan paling lambat 5 hari sebelum rapat."],
                ['Kuorum dan Pengambilan Keputusan', "{$sim}\n\nRapat sah bila dihadiri lebih dari 1/2 jumlah anggota organ. Keputusan diambil secara musyawarah untuk mufakat; bila tidak tercapai, dengan suara setuju lebih dari 1/2 suara yang dikeluarkan. Anggota yang memiliki benturan kepentingan tidak ikut mengambil keputusan atas agenda terkait. Keputusan sirkuler sah bila disetujui tertulis oleh seluruh anggota."],
                ['Risalah', "{$sim}\n\nRisalah ditandatangani seluruh anggota yang hadir. Anggota yang tidak menandatangani wajib menyampaikan alasan tertulis yang dilekatkan pada risalah. Perubahan atas risalah final dilakukan melalui addendum."],
            ]],
            'piagam_ka' => ['Piagam Komite Audit (versi demo)', 'kebijakan', 'internal', 'komite_audit', [
                ['Tugas dan Rapat', "{$sim}\n\nKomite Audit menelaah informasi keuangan, independensi auditor eksternal, efektivitas pengendalian internal, dan tindak lanjut temuan audit. Rapat paling sedikit satu kali dalam tiga bulan (POJK 55/POJK.04/2015)."],
            ]],
            'laporan_ka' => ['Laporan Komite Audit Triwulan III 2026', 'laporan_kinerja', 'rahasia', 'komite_audit', [
                ['Temuan dan Rekomendasi', "{$sim}\n\nKomite Audit mencatat 3 temuan SPI berulang terkait pengadaan bahan baku. Rekomendasi: penguatan verifikasi vendor, pemisahan fungsi pemesanan dan penerimaan, serta audit tindak lanjut pada Desember 2026."],
                ['Laporan Keuangan Triwulan III', "{$sim}\n\nKomite Audit meminta manajemen menjelaskan penurunan cadangan kerugian penurunan nilai piutang pemerintah dan menyelesaikan rekonsiliasi persediaan konsinyasi sebelum laporan keuangan triwulan III diterbitkan."],
            ]],
            'risiko_q3' => ['Laporan Profil Risiko Triwulan III 2026', 'laporan_kinerja', 'rahasia', 'komite_risiko', [
                ['Profil Risiko', "{$sim}\n\nTiga risiko berperingkat tinggi: likuiditas dan pemenuhan kewajiban pasca restrukturisasi, penurunan penjualan distribusi, dan utilisasi pabrik rendah. Indikator rasio lancar 0,61 kali masih di bawah ambang 0,80 kali."],
            ]],
            'remunerasi' => ['Kertas Usulan Remunerasi Direksi dan Dewan Komisaris 2027', 'kertas_usulan', 'sangat_rahasia', 'komite_nr', [
                ['Usulan', "{$sim}\n\nKomite Nominasi dan Remunerasi mengusulkan remunerasi 2027 tidak naik dan tantiem tidak dibayarkan selama perusahaan masih membukukan rugi. Dokumen ini hanya untuk Dewan Komisaris dan Komite Nominasi dan Remunerasi."],
            ]],
            'pengawasan_s1' => ['Laporan Pengawasan Dewan Komisaris Semester I 2026', 'laporan_kinerja', 'rahasia', 'dekom', [
                ['Kegiatan Pengawasan', "{$sim}\n\nDewan Komisaris menyelenggarakan 3 rapat internal dan 2 rapat gabungan, satu kunjungan kerja ke Plant Banjaran, serta menyampaikan 5 nasihat tertulis kepada Direksi, antara lain pemantauan arus kas 13 minggu dan percepatan optimalisasi aset."],
                ['Status Tindak Lanjut', "{$sim}\n\nDari 9 rekomendasi Dewan Komisaris semester I, 4 telah selesai terverifikasi, 4 dalam proses, dan 1 terlambat (laporan progres penjualan aset non-produktif)."],
            ]],
            'kinerja_q2' => ['Laporan Kinerja Konsolidasian Semester I 2026', 'laporan_kinerja', 'rahasia', 'gabungan', [
                ['Ringkasan Eksekutif', "{$sim}\n\nPenjualan neto semester I 2026 Rp4.450 miliar (95% dari anggaran). Rugi usaha Rp22 miliar. Kas akhir Juni Rp398 miliar."],
            ]],
            'bukti_rekon' => ['Rekonsiliasi Persediaan Konsinyasi KFTD — September 2026', 'bukti', 'rahasia', 'direksi', [
                ['Rekonsiliasi', "{$sim}\n\nRekonsiliasi saldo persediaan konsinyasi di gudang pusat KFTD per 30 September 2026. Gudang cabang belum termasuk."],
            ]],
        ];
    }
}
