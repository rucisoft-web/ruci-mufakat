<?php

namespace Database\Seeders;

use App\Models\AiInteraction;
use App\Models\AiScenario;
use App\Models\DemoScenario;
use App\Models\Organ;

/**
 * Skenario keluaran AI (referensi demo tanpa kunci API) dan panduan demonstrasi 30–40 menit.
 * Keluaran referensi disusun mengikuti skema Structured Outputs yang sama dengan keluaran model.
 */
class DemoContentSeeder
{
    public function __construct(protected array $u, protected array $o, protected array $d, protected array $x) {}

    public function run(): void
    {
        $this->aiScenarios();
        $this->demoScenarios();
    }

    protected function src(string $doc, int $page): array
    {
        return ['document_id' => $this->d[$doc]->id, 'page' => $page];
    }

    protected function out(string $summary, array $statements = [], array $questions = [], array $items = [], array $missing = [], string $draft = ''): array
    {
        return ['summary' => $summary, 'statements' => $statements, 'questions' => $questions, 'items' => $items, 'missing_information' => $missing, 'draft_text' => $draft, 'warnings' => []];
    }

    protected function s(string $text, string $type, array ...$sources): array
    {
        return ['text' => $text, 'type' => $type, 'sources' => $sources, 'unverified' => false];
    }

    protected function aiScenarios(): void
    {
        $q3 = $this->x['_m_q3'];
        $items = $q3->agendaItems()->orderBy('seq')->get();
        [$aKinerja, $aRkap, $aAset] = [$items[1], $items[2], $items[3]];
        $dirOct = \App\Models\Meeting::where('number', 'RD/10/2026')->first();

        $rows = [
            ['AI-01', 'ringkasan_rapat', 'Ringkasan sebelum rapat gabungan 29 Oktober', 'Meeting', $q3->id, 'referensi_demo', $this->out(
                'Rapat gabungan 29 Oktober membahas kinerja triwulan III, kewajiban pasca restrukturisasi, RKAP 2027, dan optimalisasi aset. Board book telah diperbarui ke versi 2: RKAP 2027 menurunkan target penjualan dan menambah analisis sensitivitas.',
                [
                    $this->s('Penjualan neto Januari–September 2026 Rp6.760 miliar atau 96% dari anggaran periode berjalan; rugi usaha menyempit menjadi Rp18 miliar.', 'fakta', $this->src('kinerja_q3', 1)),
                    $this->s('Pembayaran pokok pertama pasca restrukturisasi Rp150 miliar jatuh pada Desember 2026; kas akhir September Rp412 miliar.', 'fakta', $this->src('kinerja_q3', 3)),
                    $this->s('RKAP 2027 versi 2 menurunkan target penjualan dari Rp10.100 miliar menjadi Rp9.900 miliar dan capex dari Rp420 miliar menjadi Rp360 miliar.', 'fakta', $this->src('rkap_v1', 1), $this->src('rkap_v2', 1), $this->src('rkap_v2', 2)),
                    $this->s('Penjualan 2025 turun 7,20% dibanding 2024, sedangkan besaran rugi tahun berjalan turun 63,30%.', 'hitungan', $this->src('lt2025', 16)),
                    $this->s('Sumber pembayaran pokok 2027 masih bergantung pada hasil optimalisasi aset Rp250 miliar, padahal penilaian KJPP baru tersedia untuk 4 dari 7 aset. Ini isu utama yang perlu diuji dalam rapat.', 'analisis', $this->src('kewajiban', 2), $this->src('aset', 2)),
                ],
                ['Apa rencana pembayaran pokok 2027 bila optimalisasi aset tertunda?', 'Berapa posisi aktual DER dan DSCR terhadap ambang covenant per 30 September 2026?', 'Mengapa target penjualan 2027 tetap tumbuh 7,3% padahal penjualan 2025 turun 7,2%?', 'Bagaimana rencana menaikkan utilisasi pabrik dari 52% ke 65% memengaruhi RKAP 2027?'],
                [], ['Nilai ambang covenant per kreditur (belum dicantumkan dalam dokumen kewajiban hlm. 3).', 'Penilaian KJPP untuk 3 aset yang tersisa.'],
            )],
            ['AI-02', 'tanya_dokumen', 'Penjualan dan rugi 2025 vs 2024', null, null, 'referensi_demo', $this->out(
                'Penjualan neto 2025 Rp9.223.328 juta, turun dari Rp9.938.958 juta pada 2024. Rugi tahun berjalan konsolidasian menyempit dari Rp1.208.173 juta menjadi Rp443.357 juta.',
                [
                    $this->s('Penjualan neto 2025 Rp9.223.328 juta dan 2024 Rp9.938.958 juta.', 'fakta', $this->src('lt2025', 16)),
                    $this->s('Rugi tahun berjalan konsolidasian 2025 Rp443.357 juta dan 2024 Rp1.208.173 juta.', 'fakta', $this->src('lt2025', 16)),
                    $this->s('Penjualan turun 7,20%; besaran rugi turun 63,30% (dihitung aplikasi).', 'hitungan', $this->src('lt2025', 16)),
                    $this->s('Perbaikan rugi lebih besar daripada penurunan penjualan, menandakan perbaikan biaya dan margin bruto (33,2% pada 2025 dibanding 29,6% pada 2024), bukan pertumbuhan pendapatan.', 'analisis', $this->src('lt2025', 16)),
                ], [], [], ['Rincian penyebab penurunan rugi per pos beban tidak tersedia dalam cuplikan.'],
            ), 'Berapa penjualan neto dan rugi tahun berjalan 2025 dibanding 2024?'],
            ['AI-03', 'tanya_dokumen', 'Rasio lancar dan kejanggalan satuan', null, null, 'referensi_demo', $this->out(
                'Rasio lancar 2025 hasil hitung aplikasi adalah 0,5637 kali (56,37%). Tabel rasio laporan menulis 56,37 dengan label "x", sehingga satuannya perlu diklarifikasi.',
                [
                    $this->s('Aset lancar 31 Desember 2025 Rp3.979.719 juta dan liabilitas jangka pendek Rp7.060.554 juta.', 'fakta', $this->src('lt2025', 17)),
                    $this->s('Tabel rasio menampilkan rasio lancar 2025 sebesar 56,37 dengan label x (kali).', 'fakta', $this->src('lt2025', 19)),
                    $this->s('Aset lancar ÷ liabilitas jangka pendek = 0,5637 kali atau 56,37%.', 'hitungan', $this->src('lt2025', 17)),
                    $this->s('Angka 56,37 kemungkinan besar persentase yang diberi label satuan keliru; jika dibaca sebagai 56,37 kali, kesimpulan likuiditas akan salah total.', 'analisis', $this->src('lt2025', 19)),
                ], ['Apakah label satuan pada tabel rasio akan diralat dalam laporan berikutnya?'], [], ['Konfirmasi Direktorat Keuangan atas satuan yang benar.'],
            ), 'Berapa rasio lancar 2025 dan apakah satuannya benar?'],
            ['AI-04', 'pertanyaan_kritis', 'Pertanyaan kritis atas RKAP 2027', 'AgendaItem', $aRkap->id, 'referensi_demo', $this->out(
                'Pertanyaan berikut menguji asumsi pertumbuhan, ketergantungan pada aset, dan kepatuhan covenant dalam RKAP 2027 versi 2.',
                [
                    $this->s('Target penjualan 2027 Rp9.900 miliar (+7,3% terhadap prognosa 2026).', 'fakta', $this->src('rkap_v2', 1)),
                    $this->s('Bila penjualan 5% di bawah target, arus kas operasi tidak cukup menutup pembayaran pokok 2027 tanpa hasil optimalisasi aset.', 'fakta', $this->src('rkap_v2', 4)),
                ],
                [
                    'Apa dasar pertumbuhan penjualan 7,3% ketika penjualan 2025 turun 7,2% dan distribusi Jan–Sep 2026 masih turun 2%?',
                    'Bagaimana rencana pembayaran pokok Rp420 miliar pada 2027 bila penjualan aset Rp250 miliar mundur ke 2028?',
                    'Berapa headroom covenant DER dan DSCR pada skenario dasar dan skenario konservatif?',
                    'Mengapa capex modernisasi tetap Rp170 miliar sebelum keputusan konsolidasi pabrik dilaksanakan?',
                    'Apa asumsi kurs dan harga bahan baku impor bila rupiah melemah 5% dari Rp16.500/USD?',
                    'Indikator apa yang akan dilaporkan bulanan kepada Dewan Komisaris untuk memantau pelaksanaan RKAP?',
                ], [], ['Jadwal kewajiban per kreditur', 'Ambang covenant per kreditur', 'Skenario konservatif secara rinci'],
            )],
            ['AI-05', 'pertanyaan_kritis', 'Pertanyaan kritis atas kinerja triwulan III dan kewajiban', 'AgendaItem', $aKinerja->id, 'referensi_demo', $this->out(
                'Fokus pengawasan: kecukupan kas untuk pembayaran pokok Desember 2026 dan kelengkapan informasi covenant.',
                [$this->s('Persyaratan covenant belum mencantumkan nilai ambang dan headroom per kreditur.', 'fakta', $this->src('kewajiban', 3))],
                ['Berapa saldo kas minimum operasional yang harus dijaga setelah pembayaran pokok Rp150 miliar?', 'Kreditur mana yang mensyaratkan pembatasan dividen dan sampai kapan?', 'Apakah hari persediaan 118 hari sudah memperhitungkan stok konsinyasi KFTD?'],
                [], ['Nilai ambang covenant', 'Jadwal bunga per kreditur'],
            )],
            ['AI-06', 'cek_kelengkapan', 'Kelengkapan usulan optimalisasi aset', 'AgendaItem', $aAset->id, 'referensi_demo', $this->out(
                'Kertas usulan optimalisasi aset belum lengkap untuk persetujuan Dewan Komisaris: alternatif, analisis risiko, dan dasar kewenangan belum diisi; penilaian KJPP baru 4 dari 7 aset.',
                [$this->s('Penilaian KJPP baru tersedia untuk 4 dari 7 aset; dua aset memerlukan persetujuan pemegang saham.', 'fakta', $this->src('aset', 2))],
                ['Lengkapi alternatif (jual, kerja sama operasi, pertahankan) beserta perbandingan nilai kini bersihnya.', 'Cantumkan pasal anggaran dasar atau board manual yang menjadi dasar persetujuan.', 'Uraikan risiko harga di bawah nilai wajar dan risiko benturan kepentingan calon mitra.'],
                [], ['Alternatif yang dipertimbangkan', 'Analisis risiko', 'Dasar kewenangan', 'Penilaian KJPP 3 aset'],
            )],
            ['AI-07', 'bandingkan_versi', 'Perubahan RKAP 2027 versi 1 ke versi 2', 'Document', $this->d['rkap_v2']->id, 'referensi_demo', $this->out(
                'Versi 2 menurunkan target penjualan dan capex, memperbarui asumsi kurs, serta menambah halaman analisis sensitivitas dan covenant.',
                [
                    $this->s('Target penjualan neto: Rp10.100 miliar (v1) menjadi Rp9.900 miliar (v2).', 'fakta', $this->src('rkap_v1', 1), $this->src('rkap_v2', 1)),
                    $this->s('Asumsi kurs: Rp16.300/USD (v1) menjadi Rp16.500/USD (v2).', 'fakta', $this->src('rkap_v1', 1), $this->src('rkap_v2', 1)),
                    $this->s('Capex: Rp420 miliar (v1) menjadi Rp360 miliar (v2); modernisasi lini produksi turun dari Rp210 miliar menjadi Rp170 miliar.', 'fakta', $this->src('rkap_v1', 2), $this->src('rkap_v2', 2)),
                    $this->s('Laba bersih proyeksi: Rp35 miliar (v1) menjadi Rp12 miliar (v2).', 'fakta', $this->src('rkap_v1', 3), $this->src('rkap_v2', 3)),
                    $this->s('Halaman 4 baru: analisis sensitivitas dan covenant.', 'fakta', $this->src('rkap_v2', 4)),
                    $this->s('Penurunan target membuat RKAP lebih realistis, tetapi margin keamanan laba bersih menjadi sangat tipis.', 'analisis', $this->src('rkap_v2', 3)),
                ],
            )],
            ['AI-08', 'penjelasan_kinerja', 'Penjelasan kinerja 2025', 'Company', $this->d['lt2025']->company_id, 'referensi_demo', $this->out(
                'Kinerja 2025 menunjukkan pemulihan profitabilitas di tengah penurunan pendapatan: penjualan turun 7,20%, tetapi margin bruto naik dan besaran rugi turun 63,30%. Likuiditas tetap ketat dengan rasio lancar 0,56 kali.',
                [
                    $this->s('Margin bruto naik dari 29,65% menjadi 33,21%.', 'hitungan', $this->src('lt2025', 16)),
                    $this->s('Rasio lancar membaik dari 0,48 kali menjadi 0,56 kali, terutama karena liabilitas jangka pendek turun Rp849 miliar.', 'hitungan', $this->src('lt2025', 17)),
                    $this->s('Ekuitas turun dari Rp3.428.809 juta menjadi Rp2.735.649 juta sehingga liabilitas terhadap ekuitas naik dari 3,35 kali menjadi 4,18 kali.', 'hitungan', $this->src('lt2025', 17)),
                    $this->s('Arus kas operasi naik menjadi Rp234.364 juta, tetapi kas akhir turun menjadi Rp377.089 juta.', 'fakta', $this->src('lt2025', 18)),
                    $this->s('Perbaikan rugi didorong efisiensi dan margin, belum oleh pertumbuhan; struktur permodalan yang melemah membuat pemenuhan covenant menjadi isu pengawasan utama 2026–2027.', 'analisis', $this->src('lt2025', 8)),
                ], [], [], ['Rincian beban usaha per pos', 'Ambang covenant pasca restrukturisasi'],
            )],
            ['AI-09', 'draft_agenda', 'Draft agenda Rapat Dewan Komisaris November', 'Organ', $this->o['dekom']->id, 'referensi_demo', $this->out(
                'Usulan agenda disusun dari tugas terlambat, permintaan informasi terbuka, dan rencana kerja November.',
                [], [],
                [
                    ['title' => 'Persetujuan RKAP 2027', 'detail' => 'Persetujuan; tindak lanjut rapat gabungan 29 Oktober. Prasyarat: jawaban covenant (PI-2026-013).', 'owner' => 'Demo Direktur Keuangan & MR', 'due' => '90'],
                    ['title' => 'Nasihat atas rencana konsolidasi pabrik', 'detail' => 'Nasihat; wajib diminta sesuai Board Manual 4.4. Termasuk perbedaan pendapat Direktur Produksi.', 'owner' => 'Demo Direktur Produksi & SC', 'due' => '45'],
                    ['title' => 'Tindak lanjut terlambat: laporan progres penjualan aset', 'detail' => 'Diskusi; TL-2026-004 lewat tenggat 30 September.', 'owner' => 'Demo VP Manajemen Aset', 'due' => '20'],
                    ['title' => 'Persiapan evaluasi kinerja Direksi 2026', 'detail' => 'Diskusi; rencana kerja Desember.', 'owner' => 'Demo Komisaris A', 'due' => '30'],
                ],
            )],
            ['AI-10', 'draft_risalah', 'Draft risalah Rapat Direksi Oktober', 'Meeting', $dirOct?->id, 'referensi_demo', $this->out(
                'Draf risalah disusun dari catatan rapat. Suara dan kehadiran mengikuti data aplikasi, bukan ditebak AI.',
                [], [],
                [['title' => 'Rencana rinci transfer teknologi', 'detail' => 'Direktorat Produksi menyusun rencana rinci transfer teknologi.', 'owner' => 'Demo VP Supply Chain', 'due' => '2026-11-30']],
                [],
                "## Agenda 2 — Rencana peningkatan utilisasi pabrik\n\nDirektur Produksi dan Supply Chain menyampaikan keberatan atas jadwal transfer teknologi 18 bulan untuk dua lini steril dan mengusulkan 24 bulan. Direktur SDM abstain sampai kajian dampak SDM selesai.\n\n**Keputusan Direksi:** menyetujui konsolidasi produksi dari 9 menjadi 7 pabrik aktif (Alternatif A) dan meminta nasihat Dewan Komisaris sebelum pelaksanaan.\n\n_Draf AI — wajib ditelaah Sekretaris Perusahaan._",
            )],
            ['AI-11', 'ekstraksi_tugas', 'Ekstraksi calon tugas dari catatan', null, null, 'referensi_demo', $this->out(
                'Ditemukan 3 calon tugas; satu tanpa PIC karena tidak disebut dalam teks.',
                [], [],
                [
                    ['title' => 'Menyusun proyeksi arus kas 13 minggu', 'detail' => 'Diperbarui setiap bulan dan dilaporkan ke Dewan Komisaris.', 'owner' => 'Demo Direktur Keuangan & MR', 'due' => '2026-11-15'],
                    ['title' => 'Menyampaikan rincian covenant per kreditur', 'detail' => 'Termasuk headroom per 30 September.', 'owner' => 'Demo VP Treasury', 'due' => ''],
                    ['title' => 'Mengkaji skenario konservatif RKAP', 'detail' => 'Disebut dalam pembahasan tanpa penanggung jawab.', 'owner' => '', 'due' => ''],
                ], ['PIC untuk kajian skenario konservatif', 'Tenggat penyampaian rincian covenant'],
            ), 'Direktur Keuangan diminta menyusun proyeksi arus kas 13 minggu paling lambat 15 November; VP Treasury menyampaikan rincian covenant; perlu juga kajian skenario konservatif RKAP.'],
            ['AI-12', 'memori_keputusan', 'Memori keputusan utilisasi pabrik', null, null, 'referensi_demo', $this->out(
                'Keputusan terkait utilisasi pabrik terakhir diambil pada Rapat Direksi 13 Oktober 2026 dengan satu suara tidak setuju dan satu abstain; tindak lanjutnya sedang menunggu verifikasi.',
                [
                    $this->s('Utilisasi rata-rata 9 pabrik 52% dibanding target 65%.', 'fakta', $this->src('kinerja_q3', 4)),
                    $this->s('Rencana konsolidasi menargetkan utilisasi 70% dan penghematan Rp85 miliar per tahun mulai 2028.', 'fakta', $this->src('utilisasi', 1)),
                ], ['Apakah nasihat Dewan Komisaris sudah diminta sebelum pelaksanaan?'],
            ), 'Apa keputusan terdahulu tentang utilisasi pabrik dan konsolidasi?'],
            ['AI-13', 'terjemahan', 'Terjemahan ringkasan keputusan', null, null, 'referensi_demo', $this->out(
                'Terjemahan memakai glosarium perusahaan.', [], [], [], ['"Optimalisasi aset" tidak ada di glosarium; diterjemahkan "asset optimization".'],
                'The Board of Commissioners advises the Board of Directors to prepare a 13-week cash flow projection updated monthly and to secure a backup funding source for the principal repayment due in December 2026 under the post-restructuring arrangement.',
            ), 'Dewan Komisaris menasihatkan Direksi menyusun proyeksi arus kas 13 minggu yang diperbarui setiap bulan dan menyiapkan sumber dana cadangan untuk pembayaran pokok Desember 2026 pasca restrukturisasi.'],
            ['AI-14', 'laporan_pengawasan', 'Draft laporan pengawasan Dewan Komisaris', 'Organ', $this->o['dekom']->id, 'referensi_demo', $this->out(
                'Draf laporan pengawasan Januari–Oktober 2026.',
                [$this->s('Semester I: 5 nasihat tertulis; 4 dari 9 rekomendasi selesai terverifikasi.', 'fakta', $this->src('pengawasan_s1', 1), $this->src('pengawasan_s1', 2))],
                [], [], ['Hasil survei efektivitas rapat 2026 (masih berjalan)'],
                "# Laporan Pengawasan Dewan Komisaris (Draf)\n\n## Kegiatan\n- Rapat internal, rapat gabungan, dan kunjungan kerja sesuai rencana kerja; satu rapat tidak kuorum (19 Mei) dan dijadwalkan ulang.\n\n## Fokus pengawasan\n1. Likuiditas dan pemenuhan kewajiban pasca restrukturisasi.\n2. Utilisasi pabrik dan konsolidasi produksi.\n3. Optimalisasi aset non-produktif.\n\n## Status tindak lanjut\n- Satu tindak lanjut terlambat (laporan progres penjualan aset).\n- Satu tindak lanjut dibuka kembali (temuan SPI berulang).\n\n_Draf AI — wajib ditelaah Sekretaris Dewan Komisaris._",
            )],
            ['AI-15', 'tanya_dokumen', 'Bukti tidak cukup: jumlah rapat Dewan Komisaris 2025', null, null, 'bukti_tidak_cukup', $this->out(
                'Bukti belum tersedia. Halaman 458 Laporan Tahunan 2025 memang membahas rapat Dewan Komisaris, tetapi angka jumlah rapat dan kehadiran tidak dimuat dalam dokumen yang boleh Anda baca. AI tidak menebak angkanya.',
                [$this->s('Halaman ini memuat kebijakan rapat Dewan Komisaris dan jumlah rapat 2025, tetapi angkanya tidak dimuat dalam cuplikan.', 'fakta', $this->src('lt2025', 458))],
                [], [], ['Angka jumlah rapat Dewan Komisaris 2025 dan kehadiran per anggota (LT 2025 hlm. 458 versi lengkap).'],
            ), 'Berapa jumlah rapat Dewan Komisaris tahun 2025 dan tingkat kehadirannya?'],
            ['AI-16', 'ringkasan_rapat', 'Layanan AI timeout', 'Meeting', $this->x['_m_dk_nov']->id, 'timeout', $this->out(
                'Layanan AI tidak merespons dalam batas waktu. Ringkasan belum dapat dibuat; bahan rapat tetap dapat dibaca di ruang baca.',
                [], [], [], ['Coba lagi beberapa menit lagi atau hubungi administrator bila berulang.'],
            )],
        ];

        foreach ($rows as $r) {
            AiScenario::create(['code' => $r[0], 'feature' => $r[1], 'title' => $r[2], 'subject_type' => $r[3], 'subject_id' => $r[4], 'status' => $r[5], 'output' => $r[6], 'prompt' => $r[7] ?? $r[2]]);
        }

        // Riwayat penggunaan AI contoh.
        foreach ([['komut', 'AI-01', '2026-10-23 07:45'], ['kom1', 'AI-03', '2026-10-22 13:30'], ['kom1', 'AI-04', '2026-10-24 06:50'], ['sekdekom', 'AI-16', '2026-10-24 07:05']] as [$k, $code, $at]) {
            $s = AiScenario::where('code', $code)->first();
            AiInteraction::create(['user_id' => $this->u[$k]->id, 'feature' => $s->feature, 'subject_type' => $s->subject_type, 'subject_id' => $s->subject_id, 'prompt' => $s->prompt,
                'output' => $s->output, 'status' => $s->status, 'model' => 'referensi-demo:'.$s->code, 'is_draft' => true,
                'context_refs' => collect($s->output['statements'])->flatMap(fn ($st) => $st['sources'])->unique(fn ($x) => $x['document_id'].'-'.$x['page'])->values()->all(),
                'error' => $s->status === 'timeout' ? 'Simulasi: layanan AI tidak merespons dalam batas waktu.' : null, 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    protected function demoScenarios(): void
    {
        $rows = [
            ['Membuka dasbor', 'Dasbor Pimpinan', 'Masuk sebagai Demo Komisaris Utama, buka Dasbor Pimpinan.', 'Rapat mendatang, kesiapan bahan, permintaan informasi lewat SLA, dan tugas terlambat tampil dengan grafik.', 'dasbor-pimpinan'],
            ['Membuka dasbor', 'Beranda Saya', 'Buka Beranda Saya.', 'Daftar pribadi: bahan belum dibaca, tanda tangan, sirkuler, pertanyaan, dan tugas.', 'beranda-saya'],
            ['Menyiapkan rapat', 'Agenda dan kertas usulan', 'Buka rapat gabungan 29 Oktober → tab Agenda.', 'Setiap agenda punya tujuan, sifat, pemilik bahan, penyaji, dan organ pemutus.', 'meetings'],
            ['Menyiapkan rapat', 'Kelengkapan usulan', 'Pada agenda "Optimalisasi aset", jalankan Pemeriksaan kelengkapan.', 'Alternatif, risiko, dan dasar kewenangan ditandai belum lengkap.', 'asisten-ai'],
            ['Membaca board book', 'Versi board book', 'Buka tab Board Book.', 'Versi 2 menggantikan versi 1 dengan daftar perubahan; sidik SHA-256 setiap versi.', 'meetings'],
            ['Membaca board book', 'Ruang baca dan anotasi', 'Buka RKAP 2027 di ruang baca, tambahkan catatan pribadi.', 'Catatan pribadi hanya terlihat oleh pemiliknya; catatan bersama terlihat anggota lain.', 'ruang-baca'],
            ['Menggunakan AI', 'Ringkasan sebelum rapat', 'Asisten AI → Ringkasan sebelum rapat → rapat gabungan 29 Oktober.', 'Ringkasan dengan rujukan dokumen dan halaman; pembeda fakta, hitungan, dan analisis.', 'asisten-ai'],
            ['Menggunakan AI', 'Perhitungan rasio', 'Asisten AI → Penjelasan kinerja.', 'Rasio dihitung aplikasi; AI hanya menjelaskan.', 'asisten-ai'],
            ['Menggunakan AI', 'Pertanyaan kritis', 'Asisten AI → Pertanyaan kritis → agenda RKAP 2027.', 'Daftar pertanyaan tentang asumsi, risiko, alternatif, dan data yang belum ada.', 'asisten-ai'],
            ['Meminta klarifikasi', 'Satuan rasio lancar', 'Permintaan informasi → PI-2026-011.', 'Pertanyaan dari halaman 19, jawaban PIC, menunggu diterima penanya.', 'information-requests'],
            ['Meminta klarifikasi', 'Covenant', 'Permintaan informasi → PI-2026-013.', 'Status "Perlu klarifikasi" karena jawaban belum memuat angka.', 'information-requests'],
            ['Melaksanakan rapat', 'Kehadiran aktual dan kuorum', 'Buka Rapat Dewan Komisaris 19 Mei (tidak kuorum).', 'RSVP 4 hadir, kehadiran aktual 2; kuorum minimal 3 tidak terpenuhi.', 'meetings'],
            ['Melaksanakan rapat', 'Benturan kepentingan', 'Rapat gabungan 29 Oktober → agenda 4.', 'Demo Komisaris B mundur; bahan agenda 4 tertutup baginya dan tidak dihitung kuorum agenda.', 'meetings'],
            ['Mencatat keputusan', 'Keputusan terpisah per organ', 'Buka rapat gabungan 25 Agustus → tab Keputusan.', 'Keputusan Direksi, Nasihat Dekom, dan Persetujuan Dekom tercatat terpisah dengan suara masing-masing.', 'decisions'],
            ['Mencatat keputusan', 'Abstain dan pendapat berbeda', 'Buka keputusan utilisasi pabrik (Rapat Direksi Oktober).', 'Satu tidak setuju dengan catatan, satu abstain.', 'decisions'],
            ['Mencatat keputusan', 'Sirkuler kedaluwarsa', 'Keputusan Sirkuler → SRK-DIR/03/2026.', 'Kedaluwarsa karena 2 anggota tidak menanggapi sebelum batas waktu.', 'circular-resolutions'],
            ['Menyusun risalah', 'Tanda tangan belum lengkap', 'Rapat Direksi Oktober → tab Risalah.', 'Satu anggota menolak dengan alasan, satu belum menanggapi; risalah belum final.', 'meetings'],
            ['Menyusun risalah', 'Addendum', 'Rapat Dewan Komisaris September → tab Risalah.', 'Risalah final tidak ditimpa; koreksi berupa addendum yang tertaut.', 'meetings'],
            ['Memantau tindak lanjut', 'Bukti ditolak', 'Tindak Lanjut → TL-2026-002.', 'Bukti ditolak dengan alasan; tugas kembali berjalan.', 'tasks'],
            ['Memantau tindak lanjut', 'Dibuka kembali', 'Tindak Lanjut → TL-2026-006.', 'Tugas yang sudah selesai dibuka kembali karena temuan berulang.', 'tasks'],
            ['Memantau tindak lanjut', 'Kemajuan 100% belum selesai', 'Tindak Lanjut → TL-2026-010.', 'Kemajuan 100% tetapi status masih berjalan karena bukti belum diverifikasi.', 'tasks'],
            ['Memeriksa jejak audit', 'Rantai bukti', 'Register Keputusan → KEP-DIR rekonsiliasi persediaan → Tugas → Bukti.', 'Sumber → agenda → keputusan → tugas → bukti → verifikasi dapat ditelusuri.', 'decisions'],
            ['Memeriksa jejak audit', 'Akses ditolak', 'Jejak Audit → filter "akses_ditolak".', 'Percobaan Direksi membuka dokumen remunerasi dan admin membuka risalah tercatat.', 'audit-logs'],
            ['Kasus khusus', 'Bahan rapat terlambat', 'Rapat Komite Audit 20 Oktober.', 'Board book terbit H-1 dan ditandai melanggar aturan H-5.', 'meetings'],
            ['Kasus khusus', 'AI tanpa bukti cukup', 'Asisten AI → Tanya jawab → "Berapa jumlah rapat Dewan Komisaris 2025?"', 'AI menyatakan bukti belum tersedia dan tidak menebak.', 'asisten-ai'],
            ['Kasus khusus', 'AI timeout', 'Asisten AI → Ringkasan → Rapat Dewan Komisaris November.', 'Pesan timeout; fitur rapat lain tetap berjalan.', 'asisten-ai'],
            ['Kasus khusus', 'Hak akses berbeda', 'Masuk sebagai Demo Direktur Komersial, cari rapat Komite Nominasi dan Remunerasi.', 'Rapat dan dokumen KNR tidak tampil bagi Direksi.', 'meetings'],
            ['Kasus khusus', 'Presenter tamu terbatas', 'Masuk sebagai Demo Konsultan Restrukturisasi.', 'Hanya rapat 29 Oktober dan agenda yang ia bawakan; akses berakhir 30 Oktober.', 'meetings'],
            ['Kepatuhan', 'Frekuensi rapat POJK 33', 'Pengawasan → Risiko & Kepatuhan.', 'Realisasi rapat per organ dibandingkan aturan regulasi dan kebijakan internal.', 'kepatuhan-rapat'],
            ['Kepatuhan', 'Masa jabatan', 'Tata Kelola → Anggota Dewan.', 'Pengingat masa jabatan yang berakhir dalam 12 bulan.', 'memberships'],
        ];

        foreach ($rows as $i => $r) {
            DemoScenario::create(['code' => sprintf('SK-%02d', $i + 1), 'stage' => $r[0], 'title' => $r[1], 'steps' => $r[2], 'expected' => $r[3], 'route_hint' => $r[4], 'sort' => $i]);
        }
    }
}
