<?php

namespace Database\Seeders;

use App\Enums\DecisionKind;
use App\Enums\MeetingStatus;
use App\Enums\OrganKind;
use App\Models\AgendaItem;
use App\Models\Annotation;
use App\Models\AuthorityRule;
use App\Models\BoardBook;
use App\Models\BoardProfile;
use App\Models\CircularResolution;
use App\Models\Company;
use App\Models\ComplianceItem;
use App\Models\ConflictDeclaration;
use App\Models\Declaration;
use App\Models\Document;
use App\Models\Evaluation;
use App\Models\FinancialFact;
use App\Models\HistoricalOfficial;
use App\Models\InformationRequest;
use App\Models\Meeting;
use App\Models\MeetingRule;
use App\Models\Membership;
use App\Models\Minute;
use App\Models\Organ;
use App\Models\Policy;
use App\Models\Risk;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Models\WorkPlanItem;
use App\Services\BoardBookService;
use App\Services\CircularService;
use App\Services\DecisionService;
use App\Services\DocumentService;
use App\Services\InformationRequestService;
use App\Services\MinuteService;
use App\Services\TaskService;
use App\Support\Access;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Data demo PT Kimia Farma Tbk yang saling terhubung:
 * sumber → agenda → keputusan → tugas → bukti → verifikasi.
 *
 * Nama pejabat asli hanya ada di Direktori Historis per 31 Desember 2025.
 * Seluruh aktivitas rapat memakai alias "Demo …" sehingga keputusan, pendapat, benturan kepentingan,
 * dan keterlambatan simulasi tidak diatribusikan kepada orang nyata.
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, User> */
    protected array $u = [];

    /** @var array<string, Organ> */
    protected array $o = [];

    /** @var array<string, Document> */
    protected array $d = [];

    /** Rapat dan keputusan kunci yang dipakai lintas bagian. */
    protected array $x = [];

    protected Company $kaef;

    public function run(): void
    {
        Carbon::setTestNow('2026-01-02 08:00:00');

        try {
            DB::transaction(function () {
                $this->rules();
                $this->companies();
                $this->users();
                $this->organs();
                $this->profiles();
                $this->governance();
                $this->documents();
                $this->facts();
                $this->workPlan();
                $this->routineMeetings();
                $this->failedQuorumMeeting();
                $this->jointMeetingAugust();
                $this->dekomSeptember();
                $this->direksiSeptember();
                $this->direksiOctober();
                $this->auditCommitteeOctober();
                $this->knrJuly();
                $this->jointMeetingQ3();
                $this->upcomingMeetings();
                $this->circulars();
                $this->tasks();
                $this->informationRequests();
                $this->risksAndCompliance();
                $this->development();
                $this->extraAudit();
                (new DemoContentSeeder($this->u, $this->o, $this->d, $this->x))->run();
            });
        } finally {
            Carbon::setTestNow();
            Auth::logout();
            Access::flush();
        }
    }

    protected function at(string $datetime, ?string $actor = null): void
    {
        Carbon::setTestNow($datetime);
        Access::flush();

        if ($actor) {
            Auth::setUser($this->u[$actor]);
        }
    }

    // ------------------------------------------------------------------ dasar

    protected function rules(): void
    {
        $rows = [
            ['POJK33-DIR', 'direksi', 'Rapat Direksi', 'min_meetings_year', 12, 12, 'POJK 33/POJK.04/2014 Pasal 16: paling sedikit 1 kali setiap bulan'],
            ['POJK33-DK', 'dekom', 'Rapat Dewan Komisaris', 'min_meetings_year', 6, 8, 'POJK 33/POJK.04/2014 Pasal 31: paling sedikit 1 kali dalam 2 bulan; kebijakan internal 8 kali'],
            ['POJK33-GAB', 'gabungan', 'Rapat gabungan Dewan Komisaris dan Direksi', 'min_meetings_year', 3, 4, 'POJK 33/POJK.04/2014 Pasal 31 ayat (3): paling sedikit 1 kali dalam 4 bulan; kebijakan internal tiap triwulan'],
            ['POJK33-BAHAN', 'semua', 'Penyampaian bahan rapat terjadwal', 'material_days', 5, 5, 'POJK 33/POJK.04/2014: bahan rapat terjadwal disampaikan paling lambat 5 hari sebelum rapat'],
            ['POJK55-KA', 'komite', 'Rapat Komite Audit', 'min_meetings_year', 4, 4, 'POJK 55/POJK.04/2015: paling sedikit 1 kali dalam 3 bulan'],
            ['POJK34-KNR', 'komite', 'Rapat Komite Nominasi dan Remunerasi', 'min_meetings_year', 3, 3, 'POJK 34/POJK.04/2014: paling sedikit 1 kali dalam 4 bulan'],
            ['INT-HADIR', 'semua', 'Kehadiran anggota dalam rapat', 'attendance_pct', 75, 85, 'Kebijakan internal (simulasi): kehadiran individu minimal 85% per tahun'],
        ];

        foreach ($rows as $r) {
            MeetingRule::create(['code' => $r[0], 'organ_kind' => $r[1], 'title' => $r[2], 'metric' => $r[3], 'regulatory_value' => $r[4], 'internal_value' => $r[5], 'basis' => $r[6]]);
        }
    }

    protected function companies(): void
    {
        $src = 'Laporan Tahunan 2025 hlm. 83';
        $this->kaef = Company::create([
            'name' => 'PT Kimia Farma Tbk', 'short_name' => 'Kimia Farma', 'ticker' => 'KAEF', 'relation' => 'induk',
            'sector' => 'Farmasi terintegrasi: bahan baku, manufaktur, distribusi, ritel apotek, klinik, laboratorium',
            'description' => 'DATA DEMO. Profil mengacu pada Laporan Tahunan 2025; seluruh aktivitas rapat, keputusan, dan tugas adalah simulasi.',
            'data_origin' => 'report_fact', 'source_ref' => $src, 'is_demo' => true,
        ]);

        foreach ([
            ['PT Kimia Farma Apotek', 'KFA', null, 'Ritel apotek dan klinik'],
            ['PT Kimia Farma Trading & Distribution', 'KFTD', null, 'Distribusi farmasi dan alat kesehatan'],
            ['PT Phapros Tbk', 'Phapros', 'PEHA', 'Manufaktur farmasi'],
            ['PT Kimia Farma Sungwun Pharmacopia', 'KFSP', null, 'Bahan baku obat'],
        ] as $c) {
            Company::create(['parent_id' => $this->kaef->id, 'name' => $c[0], 'short_name' => $c[1], 'ticker' => $c[2], 'relation' => 'anak', 'sector' => $c[3],
                'data_origin' => 'report_fact', 'source_ref' => $src.' (persentase kepemilikan diverifikasi saat implementasi)', 'is_demo' => true]);
        }
    }

    protected function users(): void
    {
        $pw = config('mufakat.demo.password');
        $defs = [
            // kunci => [nama, email, jabatan, peran]
            'komut' => ['Demo Komisaris Utama', 'komut', 'Komisaris Utama / Independen', 'komisaris'],
            'kom1' => ['Demo Komisaris Independen', 'kom.independen', 'Komisaris Independen; Ketua Komite Audit', 'komisaris'],
            'kom2' => ['Demo Komisaris A', 'komisaris.a', 'Komisaris; Ketua Komite Nominasi dan Remunerasi', 'komisaris'],
            'kom3' => ['Demo Komisaris B', 'komisaris.b', 'Komisaris; Ketua Komite Pemantau Risiko', 'komisaris'],
            'kom4' => ['Demo Komisaris C', 'komisaris.c', 'Komisaris', 'komisaris'],
            'dirut' => ['Demo Direktur Utama', 'dirut', 'Direktur Utama', 'direksi'],
            'dirkeu' => ['Demo Direktur Keuangan & MR', 'dirkeu', 'Direktur Keuangan dan Manajemen Risiko', 'direksi'],
            'dirprod' => ['Demo Direktur Produksi & SC', 'dirprod', 'Direktur Produksi dan Supply Chain', 'direksi'],
            'dirkom' => ['Demo Direktur Komersial', 'dirkom', 'Direktur Komersial', 'direksi'],
            'dirsdm' => ['Demo Direktur SDM', 'dirsdm', 'Direktur Sumber Daya Manusia', 'direksi'],
            'dirport' => ['Demo Direktur Portofolio', 'dirport', 'Direktur Portofolio, Produk dan Layanan', 'direksi'],
            'ka1' => ['Demo Anggota Komite Audit 1', 'komite.audit1', 'Anggota Komite Audit (independen, akuntan)', 'komite'],
            'ka2' => ['Demo Anggota Komite Audit 2', 'komite.audit2', 'Anggota Komite Audit (independen)', 'komite'],
            'kpr' => ['Demo Anggota Komite Risiko', 'komite.risiko', 'Anggota Komite Pemantau Risiko (independen)', 'komite'],
            'sekper' => ['Demo Sekretaris Perusahaan', 'sekper', 'Sekretaris Perusahaan', 'sekper'],
            'sekdekom' => ['Demo Sekretaris Dewan Komisaris', 'sekdekom', 'Sekretaris Dewan Komisaris', 'sekdekom'],
            'treasury' => ['Demo VP Treasury', 'vp.treasury', 'VP Treasury', 'pic'],
            'akuntansi' => ['Demo VP Akuntansi', 'vp.akuntansi', 'VP Akuntansi dan Pelaporan Keuangan', 'pic'],
            'spi' => ['Demo Kepala SPI', 'kepala.spi', 'Kepala Satuan Pengawasan Intern', 'pic'],
            'scm' => ['Demo VP Supply Chain', 'vp.scm', 'VP Supply Chain Planning', 'pic'],
            'asetvp' => ['Demo VP Manajemen Aset', 'vp.aset', 'VP Manajemen Aset', 'pic'],
            'ritel' => ['Demo VP Ritel & Layanan', 'vp.ritel', 'VP Ritel dan Layanan Kesehatan', 'pic'],
            'auditor' => ['Demo Auditor Internal', 'auditor', 'Auditor (penugasan pemeriksaan tata kelola)', 'auditor'],
            'admin' => ['Demo Administrator Sistem', 'admin', 'Administrator teknis', 'admin'],
            'konsultan' => ['Demo Konsultan Restrukturisasi', 'konsultan', 'Presenter tamu — penasihat keuangan', 'tamu'],
        ];

        foreach ($defs as $key => [$name, $email, $title, $role]) {
            $this->u[$key] = User::create([
                'name' => $name, 'email' => $email.'@mufakat.demo', 'password' => $pw, 'title' => $title, 'role' => $role,
                'is_demo_alias' => true, 'email_verified_at' => now(),
                'access_until' => $key === 'konsultan' ? '2026-10-30 23:59:00' : null,
            ]);
        }

        $this->u['tamu'] = User::create([
            'name' => 'Tamu Demo', 'email' => User::DEMO_GUEST_EMAIL, 'password' => $pw, 'title' => 'Pengunjung demo (hanya lihat)',
            'role' => 'tamu_demo', 'is_demo_alias' => true, 'email_verified_at' => now(),
        ]);
    }

    protected function organs(): void
    {
        $c = $this->kaef->id;
        $this->o['direksi'] = Organ::create(['company_id' => $c, 'kind' => 'direksi', 'name' => 'Direksi', 'short_name' => 'Direksi', 'min_meetings_per_year' => 12, 'color' => '#0f766e', 'sort' => 1,
            'mandate' => 'Pengurusan perseroan sesuai maksud dan tujuan, mewakili perseroan di dalam dan di luar pengadilan.']);
        $this->o['dekom'] = Organ::create(['company_id' => $c, 'kind' => 'dekom', 'name' => 'Dewan Komisaris', 'short_name' => 'Dekom', 'min_meetings_per_year' => 6, 'color' => '#1d4ed8', 'sort' => 2,
            'mandate' => 'Pengawasan atas kebijakan pengurusan dan memberi nasihat kepada Direksi; persetujuan atas tindakan tertentu sesuai anggaran dasar.']);
        $this->o['gabungan'] = Organ::create(['company_id' => $c, 'kind' => 'gabungan', 'name' => 'Rapat Gabungan Dewan Komisaris dan Direksi', 'short_name' => 'Gabungan', 'min_meetings_per_year' => 3, 'color' => '#7c3aed', 'sort' => 3,
            'mandate' => 'Forum bersama. Kewenangan tiap organ tetap terpisah; kuorum dan suara dihitung per organ.']);
        $this->o['komite_audit'] = Organ::create(['company_id' => $c, 'parent_organ_id' => $this->o['dekom']->id, 'kind' => 'komite', 'name' => 'Komite Audit', 'short_name' => 'KA', 'min_meetings_per_year' => 4, 'color' => '#b45309', 'sort' => 4,
            'mandate' => 'Membantu Dewan Komisaris menelaah informasi keuangan, pengendalian internal, dan auditor.']);
        $this->o['komite_nr'] = Organ::create(['company_id' => $c, 'parent_organ_id' => $this->o['dekom']->id, 'kind' => 'komite', 'name' => 'Komite Nominasi dan Remunerasi', 'short_name' => 'KNR', 'min_meetings_per_year' => 3, 'color' => '#be185d', 'sort' => 5,
            'mandate' => 'Rekomendasi nominasi dan remunerasi Direksi dan Dewan Komisaris (simulasi struktur).']);
        $this->o['komite_risiko'] = Organ::create(['company_id' => $c, 'parent_organ_id' => $this->o['dekom']->id, 'kind' => 'komite', 'name' => 'Komite Pemantau Risiko', 'short_name' => 'KPR', 'min_meetings_per_year' => 4, 'color' => '#0369a1', 'sort' => 6,
            'mandate' => 'Memantau profil risiko dan efektivitas manajemen risiko (simulasi struktur).']);

        $m = fn (string $user, string $organ, string $pos, string $title, array $extra = []) => Membership::create($extra + [
            'user_id' => $this->u[$user]->id, 'organ_id' => $this->o[$organ]->id, 'position' => $pos, 'position_title' => $title,
            'term_start' => '2025-07-30', 'term_end' => '2030-06-30',
        ]);

        $m('komut', 'dekom', 'ketua', 'Komisaris Utama/Independen', ['is_independent' => true]);
        $m('kom1', 'dekom', 'anggota', 'Komisaris Independen', ['is_independent' => true, 'term_start' => '2023-05-23', 'term_end' => '2027-06-30', 'term_number' => 2]);
        $m('kom2', 'dekom', 'anggota', 'Komisaris');
        $m('kom3', 'dekom', 'anggota', 'Komisaris', ['term_start' => '2022-06-15', 'term_end' => '2027-05-31']);
        $m('kom4', 'dekom', 'anggota', 'Komisaris', ['term_start' => '2025-11-03']);
        $m('sekdekom', 'dekom', 'sekretaris', 'Sekretaris Dewan Komisaris', ['has_vote' => false, 'term_start' => '2024-01-02', 'term_end' => null]);

        $m('dirut', 'direksi', 'ketua', 'Direktur Utama', ['term_start' => '2024-06-20', 'term_end' => '2029-06-30']);
        $m('dirkeu', 'direksi', 'anggota', 'Direktur Keuangan dan Manajemen Risiko', ['term_start' => '2025-11-03']);
        foreach (['dirprod' => 'Direktur Produksi dan Supply Chain', 'dirkom' => 'Direktur Komersial', 'dirsdm' => 'Direktur SDM', 'dirport' => 'Direktur Portofolio, Produk dan Layanan'] as $k => $t) {
            $m($k, 'direksi', 'anggota', $t);
        }
        $m('sekper', 'direksi', 'sekretaris', 'Sekretaris Perusahaan', ['has_vote' => false, 'term_start' => '2024-01-02', 'term_end' => null]);

        $m('kom1', 'komite_audit', 'ketua', 'Ketua Komite Audit', ['is_independent' => true, 'term_start' => '2023-05-23', 'term_end' => '2027-06-30']);
        $m('ka1', 'komite_audit', 'anggota', 'Anggota (akuntan)', ['is_independent' => true, 'term_start' => '2024-01-02', 'term_end' => '2026-12-31']);
        $m('ka2', 'komite_audit', 'anggota', 'Anggota', ['is_independent' => true, 'term_start' => '2024-01-02', 'term_end' => '2027-12-31']);
        $m('kom2', 'komite_nr', 'ketua', 'Ketua KNR');
        $m('komut', 'komite_nr', 'anggota', 'Anggota KNR', ['is_independent' => true]);
        $m('kom4', 'komite_nr', 'anggota', 'Anggota KNR', ['term_start' => '2025-11-03']);
        $m('kom3', 'komite_risiko', 'ketua', 'Ketua KPR', ['term_start' => '2022-06-15', 'term_end' => '2027-05-31']);
        $m('kpr', 'komite_risiko', 'anggota', 'Anggota (independen)', ['is_independent' => true, 'term_start' => '2024-03-01', 'term_end' => '2027-02-28']);
        $m('kom4', 'komite_risiko', 'anggota', 'Anggota KPR', ['term_start' => '2025-11-03']);
        foreach (['komite_audit', 'komite_nr', 'komite_risiko'] as $k) {
            $m('sekdekom', $k, 'sekretaris', 'Sekretaris komite', ['has_vote' => false, 'term_start' => '2024-01-02', 'term_end' => null]);
        }
    }

    protected function profiles(): void
    {
        $p = [
            'komut' => [['Tata kelola', 'Keuangan', 'Pasar modal'], 'S3 Ekonomi', true],
            'kom1' => [['Akuntansi', 'Audit', 'Manajemen risiko'], 'S2 Akuntansi, CA', true],
            'kom2' => [['Kebijakan kesehatan', 'SDM', 'Remunerasi'], 'S2 Kesehatan Masyarakat', false],
            'kom3' => [['Manajemen risiko', 'Perbankan'], 'S2 Manajemen', false],
            'kom4' => [['Kedokteran', 'Layanan kesehatan'], 'S3 Kedokteran', false],
            'dirut' => [['Strategi', 'Transformasi', 'Farmasi'], 'S2 MBA', false],
            'dirkeu' => [['Keuangan korporat', 'Restrukturisasi utang'], 'S2 Keuangan', false],
            'dirprod' => [['Manufaktur farmasi', 'CPOB', 'Rantai pasok'], 'Apoteker, S2 Teknik Industri', false],
            'dirkom' => [['Penjualan', 'Distribusi'], 'S2 Pemasaran', false],
            'dirsdm' => [['SDM', 'Budaya organisasi'], 'S2 Psikologi', false],
            'dirport' => [['Portofolio produk', 'Layanan kesehatan'], 'Apoteker, S2 Manajemen', false],
            'ka1' => [['Akuntansi', 'Audit eksternal'], 'S2 Akuntansi, CPA', true],
            'ka2' => [['Hukum korporasi', 'Kepatuhan'], 'S2 Hukum', true],
            'kpr' => [['Manajemen risiko', 'Aktuaria'], 'S2 Aktuaria', true],
        ];

        foreach ($p as $k => [$exp, $edu, $ind]) {
            BoardProfile::create(['user_id' => $this->u[$k]->id, 'expertise' => $exp, 'education' => $edu, 'is_independent' => $ind,
                'independence_declared_at' => $ind ? '2026-01-15' : null, 'onboarded_at' => $k === 'kom4' || $k === 'dirkeu' ? '2025-11-20' : '2025-08-15',
                'bio' => 'Profil alias demo. Tidak mewakili pejabat nyata.']);
        }

        $src = 'Keterbukaan informasi RUPST 30 Jul 2025 & RUPSLB 3 Nov 2025 (CNBC Indonesia, Kontan); cocokkan LT 2025 hlm. ';
        $rows = [
            ['Stefan Looho', 'dekom', 'Komisaris Utama/Independen', '409'], ['Sumarjati Arjoso', 'dekom', 'Komisaris', '409'],
            ['Suprianto', 'dekom', 'Komisaris', '409'], ['Fachmi Idris', 'dekom', 'Komisaris', '409'], ['Diah Kusumawardani', 'dekom', 'Komisaris', '409'],
            ['Djagad Prakasa Dwialam', 'direksi', 'Direktur Utama', '424'], ['Willy Meridian', 'direksi', 'Direktur Keuangan dan Manajemen Risiko', '424'],
            ['Hadi Kardoko', 'direksi', 'Direktur Produksi dan Supply Chain', '424'], ['Hanadi Setiarto', 'direksi', 'Direktur Komersial', '424'],
            ['Disril Revolin Putra', 'direksi', 'Direktur Sumber Daya Manusia', '424'], ['Jasmine Karsono', 'direksi', 'Direktur Portofolio, Produk dan Layanan', '424'],
        ];

        foreach ($rows as $i => [$name, $organ, $pos, $page]) {
            HistoricalOfficial::create(['company_id' => $this->kaef->id, 'name' => $name, 'organ' => $organ, 'position' => $pos, 'as_of' => '2025-12-31',
                'appointment_basis' => in_array($name, ['Willy Meridian', 'Sumarjati Arjoso'], true) ? 'RUPSLB 3 November 2025' : 'RUPST 30 Juli 2025',
                'source_ref' => $src.$page, 'data_origin' => 'public_fact', 'sort' => $i]);
        }
    }

    protected function governance(): void
    {
        $c = $this->kaef->id;
        foreach ([
            ['anggaran_dasar', 'Anggaran Dasar PT Kimia Farma Tbk', null, '2025', '2025-07-30', 'Dasar kewenangan organ; ketentuan persetujuan Dewan Komisaris atas tindakan Direksi tertentu. (Ringkasan simulasi.)'],
            ['board_manual', 'Board Manual Direksi dan Dewan Komisaris', null, '3.1', '2024-09-01', 'Tata kerja rapat, kuorum, keputusan sirkuler, risalah, benturan kepentingan, dan hubungan kerja Direksi–Dekom.'],
            ['piagam', 'Piagam Komite Audit', 'komite_audit', '2.0', '2024-01-02', 'Tugas, wewenang, keanggotaan, dan rapat Komite Audit.'],
            ['piagam', 'Piagam Komite Nominasi dan Remunerasi', 'komite_nr', '1.2', '2024-01-02', 'Proses nominasi dan rekomendasi remunerasi.'],
            ['piagam', 'Piagam Komite Pemantau Risiko', 'komite_risiko', '1.1', '2024-03-01', 'Pemantauan profil risiko dan selera risiko.'],
            ['kebijakan', 'Kebijakan Benturan Kepentingan Organ Perseroan', null, '1.3', '2025-01-15', 'Pernyataan per agenda; anggota yang berbenturan tidak ikut memutus dan bahan agenda ditutup baginya.'],
            ['kebijakan', 'Kebijakan Klasifikasi Informasi Dewan', null, '1.0', '2025-03-01', 'Internal, Rahasia, Sangat Rahasia; administrator teknis tidak otomatis mengakses isi dokumen dewan.'],
            ['regulasi', 'POJK 33/POJK.04/2014 tentang Direksi dan Dewan Komisaris Emiten atau Perusahaan Publik', null, '2014', '2014-12-08', 'Frekuensi minimum rapat, rapat gabungan, dan penyampaian bahan rapat terjadwal.'],
        ] as [$kind, $title, $organ, $ver, $eff, $sum]) {
            Policy::create(['company_id' => $c, 'organ_id' => $organ ? $this->o[$organ]->id : null, 'kind' => $kind, 'title' => $title, 'version' => $ver,
                'effective_date' => $eff, 'review_due' => $kind === 'board_manual' ? '2026-12-31' : null, 'status' => 'berlaku', 'summary' => $sum,
                'data_origin' => $kind === 'regulasi' ? 'public_fact' : 'simulated']);
        }

        foreach ([
            ['Rencana Kerja dan Anggaran Perusahaan (RKAP)', 'Seluruh', 'direksi', 'dekom_persetujuan', 'Anggaran Dasar (simulasi pasal)'],
            ['Pengalihan/penghapusbukuan aset tetap', '> Rp25 miliar per transaksi', 'direksi', 'dekom_persetujuan', 'Anggaran Dasar; Board Manual 4.3'],
            ['Pinjaman jangka panjang baru atau perubahan syarat pinjaman', '> Rp500 miliar', 'direksi', 'dekom_persetujuan', 'Anggaran Dasar; perjanjian restrukturisasi'],
            ['Kerja sama operasi aset', '> 5 tahun', 'direksi', 'dekom_persetujuan', 'Board Manual 4.3'],
            ['Kebijakan harga jual dan diskon distribusi', 'Seluruh', 'direksi', 'direksi', 'Board Manual 3.2'],
            ['Konsolidasi/penutupan fasilitas produksi', 'Seluruh', 'direksi', 'dekom_nasihat', 'Board Manual 4.4 (nasihat Dekom wajib diminta)'],
            ['Penunjukan Kantor Akuntan Publik', 'Seluruh', 'dekom', 'rups', 'POJK 13/2017; rekomendasi Komite Audit'],
            ['Remunerasi Direksi dan Dewan Komisaris', 'Seluruh', 'dekom', 'rups', 'UU PT; rekomendasi KNR'],
            ['Penghapusbukuan piutang macet', '> Rp10 miliar', 'direksi', 'dekom_persetujuan', 'Anggaran Dasar (simulasi pasal)'],
        ] as [$matter, $threshold, $proposer, $approver, $basis]) {
            AuthorityRule::create(['company_id' => $c, 'matter' => $matter, 'threshold' => $threshold, 'proposer_organ' => $proposer, 'approver' => $approver, 'basis' => $basis]);
        }
    }

    protected function documents(): void
    {
        $svc = app(DocumentService::class);
        $this->at('2026-03-31 10:00:00', 'sekper');

        $lt = Document::create(['company_id' => $this->kaef->id, 'title' => 'Laporan Tahunan 2025 PT Kimia Farma Tbk (cuplikan demo)', 'kind' => 'laporan_tahunan',
            'classification' => 'internal', 'data_origin' => 'report_fact', 'source_ref' => 'Laporan_Tahunan_Tahun_Buku Kimia Farma 2025.pdf (968 halaman)',
            'uploaded_by' => $this->u['sekper']->id, 'retention_until' => '2036-12-31']);
        foreach (KimiaFarmaDocuments::annualReport() as $page => [$heading, $content]) {
            $lt->pages()->create(['page_no' => $page, 'heading' => $heading, 'content' => $content]);
        }
        $svc->refresh($lt);
        $lt->update(['page_count' => 968]);
        $this->d['lt2025'] = $lt;

        $organMap = ['gabungan' => 'gabungan', 'direksi' => 'direksi', 'dekom' => 'dekom', 'komite_audit' => 'komite_audit', 'komite_nr' => 'komite_nr', 'komite_risiko' => 'komite_risiko'];
        $dates = ['kinerja_q2' => '2026-08-14', 'pengawasan_s1' => '2026-08-20', 'utilisasi' => '2026-10-05', 'bukti_rekon' => '2026-10-09', 'laporan_ka' => '2026-10-19',
            'risiko_q3' => '2026-10-14', 'remunerasi' => '2026-07-01', 'boardmanual' => '2024-09-01', 'piagam_ka' => '2024-01-02',
            'kinerja_q3' => '2026-10-20', 'kewajiban' => '2026-10-20', 'rkap_v1' => '2026-10-20', 'aset' => '2026-10-21', 'rkap_v2' => '2026-10-23'];

        foreach (KimiaFarmaDocuments::simulated() as $key => [$title, $kind, $class, $organ, $pages]) {
            $this->at(($dates[$key] ?? '2026-10-01').' 09:00:00', 'sekper');
            $attrs = ['company_id' => $this->kaef->id, 'organ_id' => $organ ? $this->o[$organMap[$organ]]->id : null, 'title' => $title, 'kind' => $kind,
                'classification' => $class, 'data_origin' => 'simulated', 'uploaded_by' => $this->u['sekper']->id, 'series_key' => str_starts_with($key, 'rkap') ? 'rkap-2027' : $key];

            $doc = $key === 'rkap_v2'
                ? $svc->newVersion($this->d['rkap_v1'], ['title' => $title, 'change_summary' => 'Target penjualan diturunkan menjadi Rp9.900 miliar, capex menjadi Rp360 miliar, ditambah halaman analisis sensitivitas dan covenant.'], $this->u['sekper'])
                : Document::create($attrs);

            foreach ($pages as $i => [$heading, $content]) {
                $doc->pages()->create(['page_no' => $i + 1, 'heading' => $heading, 'content' => $content]);
            }
            $svc->refresh($doc);
            $this->d[$key] = $doc;
        }

        $this->d['kewajiban']->update(['ai_allowed' => true]);
        Audit::log('dokumen_unggah', 'Laporan Tahunan 2025 (cuplikan demo) diunggah ke pustaka', $lt);
    }

    protected function facts(): void
    {
        $c = $this->kaef->id;
        $lt = $this->d['lt2025']->id;
        $rows = [
            ['penjualan', 'Penjualan neto', 9223328, 9938958, '16'], ['laba_bruto', 'Laba bruto', 3063410, 2946473, '16'],
            ['laba_usaha', 'Laba (rugi) usaha', -79759, -810720, '16'], ['laba_bersih', 'Laba (rugi) tahun berjalan konsolidasian', -443357, -1208173, '16'],
            ['aset_lancar', 'Aset lancar', 3979719, 3798747, '17'], ['liabilitas_pendek', 'Liabilitas jangka pendek', 7060554, 7910053, '17'],
            ['total_aset', 'Total aset', 14183178, 14928544, '17'], ['ekuitas', 'Ekuitas', 2735649, 3428809, '17'],
            ['arus_kas_operasi', 'Arus kas bersih dari aktivitas operasi', 234364, 153551, '18'], ['kas_akhir', 'Kas dan setara kas akhir periode', 377089, 437589, '18'],
        ];

        foreach ($rows as [$m, $label, $v25, $v24, $page]) {
            foreach (['2025' => $v25, '2024' => $v24] as $period => $value) {
                FinancialFact::create(['company_id' => $c, 'metric' => $m, 'label' => $label, 'period' => (string) $period, 'value' => $value, 'unit' => 'Rp juta',
                    'data_origin' => 'report_fact', 'document_id' => $lt, 'source_page' => $page]);
            }
        }

        FinancialFact::create(['company_id' => $c, 'metric' => 'rasio_lancar_sumber', 'label' => 'Rasio lancar (angka tertulis di tabel rasio)', 'period' => '2025', 'value' => 56.37,
            'unit' => 'x (kali)', 'data_origin' => 'report_fact', 'document_id' => $lt, 'source_page' => '19', 'needs_clarification' => true,
            'quality_note' => 'Tabel menampilkan 56,37 dengan label x/kali, sedangkan aset lancar ÷ liabilitas jangka pendek = 0,5637 kali (56,37%). Nilai sumber dipertahankan; satuan perlu diklarifikasi kepada Direktorat Keuangan.']);

        foreach ([['segmen_distribusi', 'Penjualan segmen distribusi', 4330], ['segmen_ritel', 'Penjualan segmen ritel', 3360], ['segmen_manufaktur', 'Penjualan segmen manufaktur', 729], ['segmen_lainnya', 'Penjualan segmen lainnya', 804]] as [$m, $l, $v]) {
            FinancialFact::create(['company_id' => $c, 'metric' => $m, 'label' => $l, 'period' => '2025', 'value' => $v, 'unit' => 'Rp miliar', 'data_origin' => 'derived_from_report', 'document_id' => $lt, 'source_page' => '21', 'quality_note' => 'Pembulatan dari tabel segmen.']);
        }
        foreach ([['jumlah_pabrik', 'Pabrik', 9], ['jumlah_cabang_distribusi', 'Cabang distribusi', 45], ['jumlah_apotek', 'Apotek', 1050], ['jumlah_klinik', 'Klinik', 386], ['jumlah_lab', 'Laboratorium klinik', 65]] as [$m, $l, $v]) {
            FinancialFact::create(['company_id' => $c, 'metric' => $m, 'label' => $l, 'period' => '2025', 'value' => $v, 'unit' => 'unit', 'data_origin' => 'report_fact', 'document_id' => $lt, 'source_page' => '22']);
        }

        // Triwulanan 2026: simulasi (kumulatif per triwulan, Rp juta).
        $q = [
            'penjualan' => [2180400, 2269600, 2310000], 'laba_bruto' => [718000, 762000, 790000], 'laba_usaha' => [-14000, -8000, 4000],
            'laba_bersih' => [-81000, -72000, -61000], 'arus_kas_operasi' => [41000, 63000, 82000], 'kas_akhir' => [356000, 398000, 412000],
        ];
        $labels = collect($rows)->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);
        foreach ($q as $m => $vals) {
            foreach ($vals as $i => $v) {
                FinancialFact::create(['company_id' => $c, 'metric' => $m, 'label' => $labels[$m], 'period' => '2026-Q'.($i + 1), 'period_type' => 'kuartal', 'value' => $v,
                    'unit' => 'Rp juta', 'data_origin' => 'simulated', 'document_id' => $i === 2 ? $this->d['kinerja_q3']->id : null, 'source_page' => $i === 2 ? '1' : null,
                    'quality_note' => $m === 'kas_akhir' ? 'Saldo akhir triwulan.' : 'Nilai per triwulan (bukan kumulatif).']);
            }
        }
    }

    protected function workPlan(): void
    {
        $plan = [
            'dekom' => [[1, 'rapat', 'Rapat Dewan Komisaris — evaluasi kinerja 2025'], [3, 'rapat', 'Rapat Dewan Komisaris — persiapan RUPST'], [3, 'kunjungan_kerja', 'Kunjungan kerja Plant Banjaran'],
                [4, 'pelatihan', 'Pelatihan tata kelola ESG untuk dewan'], [5, 'rapat', 'Rapat Dewan Komisaris — kinerja triwulan I'], [7, 'rapat', 'Rapat Dewan Komisaris — kinerja semester I'],
                [8, 'kunjungan_kerja', 'Kunjungan kerja KFTD cabang Surabaya'], [8, 'laporan', 'Laporan pengawasan semester I'], [9, 'rapat', 'Rapat Dewan Komisaris — likuiditas dan kewajiban'],
                [10, 'program_pengawasan', 'Telaah RKAP 2027'], [11, 'rapat', 'Rapat Dewan Komisaris — persetujuan RKAP 2027'], [11, 'kunjungan_kerja', 'Kunjungan kerja Apotek dan Klinik Kimia Farma Bandung'],
                [12, 'program_pengawasan', 'Evaluasi kinerja Direksi 2026'], [12, 'pelatihan', 'Pelatihan manajemen risiko siber']],
            'komite_audit' => [[1, 'rapat', 'Telaah laporan keuangan audit 2025'], [4, 'rapat', 'Telaah laporan keuangan triwulan I'], [7, 'rapat', 'Telaah laporan keuangan semester I'], [10, 'rapat', 'Telaah laporan keuangan triwulan III'], [12, 'program_pengawasan', 'Evaluasi kinerja KAP']],
        ];

        foreach ($plan as $organ => $items) {
            foreach ($items as [$month, $kind, $title]) {
                WorkPlanItem::create(['organ_id' => $this->o[$organ]->id, 'year' => 2026, 'month' => $month, 'kind' => $kind, 'title' => $title,
                    'status' => $month < 10 ? ($title === 'Pelatihan tata kelola ESG untuk dewan' ? 'ditunda' : 'terlaksana') : ($month === 10 ? 'terlaksana' : 'direncanakan'),
                    'notes' => $title === 'Pelatihan tata kelola ESG untuk dewan' ? 'Ditunda ke Desember karena bentrok RUPST.' : null]);
            }
        }
    }

    // ------------------------------------------------------------------ rapat

    /** Peserta default: seluruh anggota organ (untuk gabungan: Direksi + Dekom) + sekretaris. */
    protected function members(string $organ): array
    {
        return match ($organ) {
            'direksi' => ['dirut', 'dirkeu', 'dirprod', 'dirkom', 'dirsdm', 'dirport'],
            'dekom' => ['komut', 'kom1', 'kom2', 'kom3', 'kom4'],
            'gabungan' => ['komut', 'kom1', 'kom2', 'kom3', 'kom4', 'dirut', 'dirkeu', 'dirprod', 'dirkom', 'dirsdm', 'dirport'],
            'komite_audit' => ['kom1', 'ka1', 'ka2'],
            'komite_nr' => ['kom2', 'komut', 'kom4'],
            'komite_risiko' => ['kom3', 'kpr', 'kom4'],
        };
    }

    protected function secretaryOf(string $organ): string
    {
        return in_array($organ, ['direksi'], true) ? 'sekper' : ($organ === 'gabungan' ? 'sekper' : 'sekdekom');
    }

    protected function chairOf(string $organ): string
    {
        return ['direksi' => 'dirut', 'dekom' => 'komut', 'gabungan' => 'komut', 'komite_audit' => 'kom1', 'komite_nr' => 'kom2', 'komite_risiko' => 'kom3'][$organ];
    }

    /**
     * @param  array<string, bool|string>  $absent  kunci => alasan
     */
    protected function meeting(string $organ, string $date, string $title, string $number, array $opts = []): Meeting
    {
        $start = Carbon::parse($date);
        $meeting = Meeting::create([
            'organ_id' => $this->o[$organ]->id,
            'number' => $number,
            'title' => $title,
            'kind' => $organ === 'gabungan' ? 'gabungan' : ($opts['kind'] ?? 'rutin'),
            'mode' => $opts['mode'] ?? 'hybrid',
            'scheduled_at' => $start,
            'ends_at' => $start->copy()->addHours($opts['hours'] ?? 3),
            'location' => $opts['location'] ?? 'Ruang Rapat Dewan, Gedung Kimia Farma, Jl. Veteran No. 9, Jakarta',
            'online_link' => 'https://meet.contoh.co.id/'.strtolower(str_replace(['/', ' '], '-', $number)),
            'status' => $opts['status'] ?? MeetingStatus::Selesai,
            'chair_id' => $this->u[$this->chairOf($organ)]->id,
            'secretary_id' => $this->u[$this->secretaryOf($organ)]->id,
            'materials_due_at' => $start->copy()->subDays(5)->setTime(17, 0),
            'is_closed_session' => $opts['closed'] ?? in_array($organ, ['dekom', 'komite_nr'], true),
            'data_origin' => 'simulated',
            'notes' => $opts['notes'] ?? null,
        ]);

        $absent = $opts['absent'] ?? [];
        $future = in_array($opts['status'] ?? null, [MeetingStatus::BahanTerbit, MeetingStatus::Dijadwalkan, MeetingStatus::Draf], true);
        $organOf = fn (string $k) => $organ === 'gabungan' ? (str_starts_with($k, 'dir') ? $this->o['direksi'] : $this->o['dekom']) : $this->o[$organ];

        foreach ($this->members($organ) as $k) {
            $isAbsent = array_key_exists($k, $absent);
            $meeting->participants()->create([
                'user_id' => $this->u[$k]->id, 'organ_id' => $organOf($k)->id, 'role' => 'anggota', 'has_vote' => true,
                'rsvp' => $opts['rsvp'][$k] ?? ($isAbsent ? 'tidak_hadir' : 'hadir'),
                'attended' => $future ? null : ! $isAbsent,
                'attendance_mode' => $future || $isAbsent ? null : ($k === 'kom4' ? 'daring' : 'luring'),
                'check_in_at' => $future || $isAbsent ? null : $start->copy()->addMinutes(rand(-10, 5)),
                'check_out_at' => $future || $isAbsent ? null : $start->copy()->addHours($opts['hours'] ?? 3)->addMinutes(($opts['early_leave'][$k] ?? 0) * -1),
                'absence_reason' => $isAbsent ? (is_string($absent[$k]) ? $absent[$k] : 'Tugas lain') : null,
            ]);
        }

        $sec = $this->secretaryOf($organ);
        $meeting->participants()->create(['user_id' => $this->u[$sec]->id, 'role' => 'sekretaris', 'has_vote' => false, 'rsvp' => 'hadir', 'attended' => $future ? null : true]);
        if ($organ === 'gabungan') {
            $meeting->participants()->create(['user_id' => $this->u['sekdekom']->id, 'role' => 'sekretaris', 'has_vote' => false, 'rsvp' => 'hadir', 'attended' => $future ? null : true]);
        }

        foreach ($opts['guests'] ?? [] as $k => $role) {
            $meeting->participants()->create(['user_id' => $this->u[$k]->id, 'role' => $role, 'has_vote' => false, 'rsvp' => 'hadir', 'attended' => $future ? null : true,
                'access_until' => $this->u[$k]->access_until]);
        }

        return $meeting;
    }

    protected function agenda(Meeting $m, int $seq, string $title, string $purpose, array $extra = []): AgendaItem
    {
        return $m->agendaItems()->create($extra + [
            'seq' => $seq, 'title' => $title, 'purpose' => $purpose,
            'duration_min' => $extra['duration_min'] ?? 30,
            'classification' => $extra['classification'] ?? 'rahasia',
            'materials_submitted_at' => $m->materials_due_at->copy()->subDay(),
            'status' => $m->scheduled_at->lessThan('2026-10-24') ? 'selesai' : 'direncanakan',
        ]);
    }

    protected function attach(AgendaItem $item, string ...$docs): void
    {
        $item->documents()->attach(collect($docs)->map(fn ($k) => $this->d[$k]->id)->all());
    }

    /** Terbitkan board book pada waktu tertentu memakai layanan sebenarnya. */
    protected function publish(Meeting $m, string $when, string $actor, ?string $note = null): BoardBook
    {
        $this->at($when, $actor);

        return app(BoardBookService::class)->publish($m->fresh(), $this->u[$actor], $note);
    }

    protected function decide(AgendaItem $item, DecisionKind $kind, string $text, array $votes = [], array $dissents = [], array $data = []): \App\Models\Decision
    {
        $this->at($item->meeting->scheduled_at->copy()->addHour()->toDateTimeString(), $this->secretaryOf($this->organKey($item->meeting)));
        $v = collect($votes)->mapWithKeys(fn ($vote, $k) => [$this->u[$k]->id => $vote])->all();
        $ds = collect($dissents)->mapWithKeys(fn ($note, $k) => [$this->u[$k]->id => $note])->all();

        return app(DecisionService::class)->record($item->fresh(), $kind, $data + ['text' => $text], $v, $ds);
    }

    protected function organKey(Meeting $m): string
    {
        return array_search($m->organ_id, collect($this->o)->map->id->all(), true);
    }

    /** Risalah lengkap: draf → edaran → seluruh penanda tangan menandatangani → final. */
    protected function minutesSigned(Meeting $m, array $refusals = [], array $pending = []): Minute
    {
        $sec = $this->secretaryOf($this->organKey($m));
        $svc = app(MinuteService::class);
        $this->at($m->scheduled_at->copy()->addDays(2)->setTime(10, 0)->toDateTimeString(), $sec);
        $minute = $svc->createDraft($m->fresh(), $this->u[$sec]);
        $this->at($m->scheduled_at->copy()->addDays(3)->setTime(10, 0)->toDateTimeString(), $sec);
        $svc->sendForReview($minute);
        $this->at($m->scheduled_at->copy()->addDays(5)->setTime(9, 0)->toDateTimeString(), $sec);
        $svc->circulate($minute->fresh());

        $day = 5;
        foreach ($minute->fresh()->signatures()->with('user')->get() as $sig) {
            $key = array_search($sig->user_id, collect($this->u)->map->id->all(), true);
            if (in_array($key, $pending, true)) {
                continue;
            }
            $this->at($m->scheduled_at->copy()->addDays(++$day > 9 ? 9 : $day)->setTime(14, rand(0, 59))->toDateTimeString(), $key);
            isset($refusals[$key]) ? $svc->refuse($minute->fresh(), $sig->user, $refusals[$key]) : $svc->sign($minute->fresh(), $sig->user);
        }

        return $minute->fresh();
    }

    protected function routineMeetings(): void
    {
        $topics = [
            'direksi' => [
                ['Evaluasi kinerja bulanan dan prognosa', 'diskusi'], ['Pemantauan arus kas 13 minggu', 'informasi'], ['Status program transformasi', 'informasi'],
                ['Pengadaan bahan baku strategis', 'keputusan'], ['Kebijakan diskon distribusi', 'keputusan'], ['Pengembangan layanan klinik dan laboratorium', 'diskusi'],
            ],
            'dekom' => [['Telaah kinerja dan likuiditas', 'diskusi'], ['Laporan komite-komite', 'informasi'], ['Tindak lanjut rekomendasi Dewan Komisaris', 'diskusi'], ['Usulan yang memerlukan persetujuan Dewan Komisaris', 'persetujuan']],
            'komite_audit' => [['Telaah laporan keuangan', 'diskusi'], ['Tindak lanjut temuan SPI', 'diskusi'], ['Rencana kerja audit internal', 'informasi']],
            'komite_risiko' => [['Profil risiko triwulanan', 'diskusi'], ['Indikator risiko utama', 'informasi']],
            'komite_nr' => [['Evaluasi kinerja individu', 'diskusi'], ['Rencana suksesi', 'informasi']],
            'gabungan' => [['Kinerja konsolidasian', 'diskusi'], ['Rencana strategis dan RKAP', 'diskusi']],
        ];

        $schedule = [
            'direksi' => ['2026-01-13', '2026-02-10', '2026-03-10', '2026-04-14', '2026-05-12', '2026-06-09', '2026-07-14', '2026-08-11'],
            'dekom' => ['2026-01-20', '2026-03-17', '2026-05-26', '2026-07-21'],
            'gabungan' => ['2026-02-24', '2026-06-23'],
            'komite_audit' => ['2026-01-27', '2026-04-21', '2026-07-28'],
            'komite_risiko' => ['2026-02-17', '2026-05-19', '2026-08-18', '2026-10-21'],
            'komite_nr' => ['2026-03-03'],
        ];
        $labels = ['direksi' => 'Rapat Direksi', 'dekom' => 'Rapat Dewan Komisaris', 'gabungan' => 'Rapat Gabungan Dewan Komisaris dan Direksi', 'komite_audit' => 'Rapat Komite Audit', 'komite_risiko' => 'Rapat Komite Pemantau Risiko', 'komite_nr' => 'Rapat Komite Nominasi dan Remunerasi'];
        $codes = ['direksi' => 'RD', 'dekom' => 'RDK', 'gabungan' => 'RGAB', 'komite_audit' => 'RKA', 'komite_risiko' => 'RKPR', 'komite_nr' => 'RKNR'];
        $decisionBudget = ['direksi' => [1, 3, 5, 7], 'dekom' => [0, 2], 'komite_audit' => [1], 'gabungan' => [0]];

        foreach ($schedule as $organ => $dates) {
            foreach ($dates as $i => $date) {
                $month = Carbon::parse($date)->translatedFormat('F Y');
                $absent = ($i % 3 === 1) ? [$this->members($organ)[count($this->members($organ)) - 1] => 'Perjalanan dinas'] : [];
                $m = $this->meeting($organ, $date.' 09:00', "{$labels[$organ]} {$month}", sprintf('%s/%02d/2026', $codes[$organ], $i + 1), ['absent' => $absent]);
                $items = [];
                foreach (array_slice($topics[$organ], 0, $organ === 'direksi' ? 3 : 2) as $j => [$t, $p]) {
                    $pick = $organ === 'direksi' ? $topics[$organ][($i + $j) % count($topics[$organ])] : [$t, $p];
                    $items[] = $this->agenda($m, $j + 1, $pick[0], $pick[1], ['presenter_id' => $this->u[$organ === 'direksi' ? ['dirkeu', 'dirkom', 'dirprod', 'dirport'][$j % 4] : $this->chairOf($organ)]->id,
                        'objective' => "Membahas {$pick[0]} periode {$month}.", 'discussion' => "Peserta membahas {$pick[0]}. Tidak ada catatan berbeda pendapat."]);
                }

                if ($organ === 'gabungan' && $i === 0) {
                    $this->attach($items[0], 'lt2025');
                }

                $this->publish($m, Carbon::parse($date)->subDays(6)->setTime(16, 0)->toDateTimeString(), $this->secretaryOf($organ));

                if (in_array($i, $decisionBudget[$organ] ?? [], true)) {
                    $kind = match ($organ) {
                        'direksi' => DecisionKind::KeputusanDireksi, 'dekom' => DecisionKind::NasihatDekom,
                        'komite_audit' => DecisionKind::RekomendasiKomite, 'gabungan' => DecisionKind::NasihatDekom,
                    };
                    $target = collect($items)->first(fn ($it) => in_array($it->purpose->value, ['keputusan', 'persetujuan'], true)) ?? $items[0];
                    $text = match ($organ) {
                        'direksi' => "Direksi menyetujui {$target->title} periode {$month} sesuai usulan, dengan pemantauan dampak arus kas oleh Direktorat Keuangan.",
                        'dekom', 'gabungan' => "Dewan Komisaris menyampaikan nasihat agar Direksi memprioritaskan perbaikan arus kas operasi dan melaporkan kemajuannya setiap bulan.",
                        'komite_audit' => 'Komite Audit merekomendasikan kepada Dewan Komisaris agar manajemen menuntaskan temuan SPI berulang sebelum akhir triwulan.',
                    };
                    $voters = $organ === 'direksi' ? collect($this->members($organ))->diff(array_keys($absent))->mapWithKeys(fn ($k) => [$k => 'setuju'])->all() : [];
                    $this->decide($target, $kind, $text, $voters);
                }

                $this->minutesSigned($m);
            }
        }
    }

    protected function failedQuorumMeeting(): void
    {
        $m = $this->meeting('dekom', '2026-05-19 14:00', 'Rapat Dewan Komisaris Mei 2026 (tidak kuorum)', 'RDK/03A/2026', [
            'status' => MeetingStatus::GagalKuorum,
            'absent' => ['kom2' => 'Sakit', 'kom3' => 'Bentrok dengan RUPS entitas lain', 'kom4' => 'Perjalanan luar negeri'],
            'notes' => 'Hanya 2 dari 5 anggota hadir; kuorum (lebih dari 1/2 = minimal 3) tidak terpenuhi. Rapat ditunda dan dijadwalkan ulang pada 26 Mei 2026. RSVP sebelumnya menunjukkan 4 anggota akan hadir.',
            'rsvp' => ['kom2' => 'hadir', 'kom3' => 'hadir', 'kom4' => 'tidak_hadir'],
        ]);
        $this->agenda($m, 1, 'Telaah kinerja triwulan I 2026', 'diskusi');
        $this->agenda($m, 2, 'Persetujuan rencana pengalihan aset gudang Medan', 'persetujuan');
        $m->agendaItems()->update(['status' => 'ditunda']);
        $this->publish($m, '2026-05-12 16:00', 'sekdekom');
        $this->at('2026-05-19 14:40', 'sekdekom');
        Audit::log('rapat_gagal_kuorum', 'Rapat Dewan Komisaris 19 Mei 2026 tidak kuorum (2 dari minimal 3)', $m);
    }

    protected function jointMeetingAugust(): void
    {
        $m = $this->meeting('gabungan', '2026-08-25 09:00', 'Rapat Gabungan Dewan Komisaris dan Direksi — Kinerja Semester I 2026', 'RGAB/03/2026', [
            'absent' => ['dirsdm' => 'Mendampingi audit BPOM'], 'hours' => 4,
        ]);
        $a1 = $this->agenda($m, 1, 'Kinerja konsolidasian semester I 2026', 'diskusi', ['presenter_id' => $this->u['dirkeu']->id, 'owner_id' => $this->u['akuntansi']->id,
            'objective' => 'Menelaah capaian semester I terhadap RKAP 2026.', 'discussion' => 'Penjualan semester I mencapai 95% anggaran. Dewan Komisaris menyoroti ketergantungan pada optimalisasi aset untuk menutup kewajiban 2027.']);
        $a2 = $this->agenda($m, 2, 'Penetapan target internal triwulan IV 2026', 'keputusan', ['presenter_id' => $this->u['dirut']->id, 'deciding_organ' => 'direksi',
            'objective' => 'Direksi menetapkan target internal triwulan IV.', 'alternatives' => 'A: target sesuai RKAP; B: target realistis berbasis prognosa.', 'financial_impact' => 'Penjualan triwulan IV Rp2,46 triliun.', 'risks' => 'Risiko pasokan bahan baku impor.', 'recommendation' => 'Alternatif B.', 'authority_basis' => 'Kewenangan pengurusan Direksi (Board Manual 3.1)',
            'discussion' => 'Direksi memutuskan target realistis. Dewan Komisaris mencatat dan memberi nasihat terpisah.']);
        $a3 = $this->agenda($m, 3, 'Persetujuan perpanjangan kerja sama operasi gudang Cikarang', 'persetujuan', ['presenter_id' => $this->u['dirprod']->id, 'deciding_organ' => 'dekom',
            'objective' => 'Meminta persetujuan Dewan Komisaris atas KSO lebih dari 5 tahun.', 'alternatives' => 'Perpanjang 7 tahun / tender ulang.', 'financial_impact' => 'Penghematan sewa Rp6 miliar per tahun.', 'risks' => 'Ketergantungan pada satu mitra logistik.', 'recommendation' => 'Perpanjang 7 tahun dengan klausul evaluasi tahunan.', 'authority_basis' => 'Board Manual 4.3 — KSO > 5 tahun',
            'discussion' => 'Dewan Komisaris menyetujui dengan syarat klausul evaluasi kinerja tahunan dan hak pengakhiran.']);
        $this->attach($a1, 'kinerja_q2', 'lt2025');
        $this->publish($m, '2026-08-18 15:00', 'sekper');

        // Rapat gabungan: hasil tiap organ dicatat terpisah, suara tidak digabung.
        $this->decide($a2, DecisionKind::KeputusanDireksi, 'Direksi menetapkan target internal penjualan triwulan IV 2026 sebesar Rp2,46 triliun dan target arus kas operasi Rp70 miliar.', ['dirut' => 'setuju', 'dirkeu' => 'setuju', 'dirprod' => 'setuju', 'dirkom' => 'setuju', 'dirport' => 'setuju']);
        $this->decide($a2, DecisionKind::NasihatDekom, 'Dewan Komisaris menasihatkan agar target triwulan IV disertai rencana kontinjensi pasokan bahan baku impor dan dilaporkan dalam rapat Oktober.');
        $this->decide($a3, DecisionKind::PersetujuanDekom, 'Dewan Komisaris menyetujui perpanjangan KSO gudang Cikarang selama 7 tahun dengan syarat klausul evaluasi kinerja tahunan dan hak pengakhiran.',
            ['komut' => 'setuju', 'kom1' => 'setuju', 'kom2' => 'setuju', 'kom3' => 'abstain', 'kom4' => 'setuju'], [], ['result' => 'disetujui_bersyarat', 'conditions' => 'Klausul evaluasi kinerja tahunan dan hak pengakhiran wajib dimuat dalam perjanjian.']);
        $this->minutesSigned($m);
    }

    protected function dekomSeptember(): void
    {
        $m = $this->meeting('dekom', '2026-09-22 13:00', 'Rapat Dewan Komisaris September 2026', 'RDK/05/2026', ['guests' => ['dirkeu' => 'undangan', 'treasury' => 'presenter']]);
        $a1 = $this->agenda($m, 1, 'Likuiditas dan kesiapan pembayaran pokok Desember 2026', 'nasihat', ['presenter_id' => $this->u['treasury']->id, 'owner_id' => $this->u['treasury']->id,
            'objective' => 'Memastikan kesiapan kas untuk pembayaran pokok Rp150 miliar.', 'discussion' => 'VP Treasury memaparkan proyeksi kas. Dewan Komisaris meminta proyeksi arus kas 13 minggu dilaporkan rutin.']);
        $a2 = $this->agenda($m, 2, 'Persetujuan pengalihan aset gudang Medan', 'persetujuan', ['presenter_id' => $this->u['dirkeu']->id, 'deciding_organ' => 'dekom',
            'objective' => 'Persetujuan pengalihan aset non-produktif > Rp25 miliar.', 'alternatives' => 'Jual / sewakan / pertahankan.', 'financial_impact' => 'Hasil penjualan perkiraan Rp38 miliar.', 'risks' => 'Harga di bawah nilai wajar.', 'recommendation' => 'Jual melalui lelang terbuka.', 'authority_basis' => 'Board Manual 4.3',
            'discussion' => 'Dewan Komisaris menyetujui dengan syarat harga minimal sesuai penilaian KJPP.']);
        $this->attach($a1, 'kinerja_q2');
        $this->publish($m, '2026-09-15 15:00', 'sekdekom');
        $d1 = $this->decide($a1, DecisionKind::NasihatDekom, 'Dewan Komisaris menasihatkan Direksi menyusun proyeksi arus kas 13 minggu yang diperbarui setiap bulan dan menyiapkan sumber dana cadangan untuk pembayaran pokok Desember 2026.');
        $d2 = $this->decide($a2, DecisionKind::PersetujuanDekom, 'Dewan Komisaris menyetujui pengalihan aset gudang Medan melalui lelang terbuka dengan harga limit tidak kurang dari nilai wajar menurut KJPP sebesar Rp38,4 miliar.',
            ['komut' => 'setuju', 'kom1' => 'setuju', 'kom2' => 'setuju', 'kom3' => 'setuju', 'kom4' => 'setuju'], [], ['result' => 'disetujui_bersyarat', 'conditions' => 'Harga limit minimal nilai wajar KJPP.']);
        $minute = $this->minutesSigned($m);

        // Koreksi setelah final: addendum, risalah asal tidak ditimpa.
        $this->at('2026-10-02 10:00', 'sekdekom');
        $svc = app(MinuteService::class);
        $add = $svc->correct($minute, 'Salah ketik nilai wajar KJPP pada agenda 2 (tertulis Rp34,8 miliar, seharusnya Rp38,4 miliar).',
            "# Addendum Risalah Rapat Dewan Komisaris 22 September 2026\n\nMengoreksi risalah versi {$minute->version_no}: pada agenda 2, nilai wajar menurut KJPP tertulis Rp34,8 miliar; yang benar Rp38,4 miliar. Bagian lain risalah tetap berlaku.", $this->u['sekdekom']);
        $svc->circulate($add);
        foreach ($add->fresh()->signatures()->with('user')->get() as $sig) {
            $this->at('2026-10-05 11:'.str_pad((string) rand(0, 59), 2, '0', STR_PAD_LEFT), array_search($sig->user_id, collect($this->u)->map->id->all(), true));
            $svc->sign($add->fresh(), $sig->user);
        }

        $this->x['_d_likuiditas'] = $d1; // disimpan untuk tugas
        $this->x['_d_medan'] = $d2;
    }

    protected function direksiSeptember(): void
    {
        $m = $this->meeting('direksi', '2026-09-08 09:00', 'Rapat Direksi September 2026', 'RD/09/2026', ['guests' => ['akuntansi' => 'presenter']]);
        $a1 = $this->agenda($m, 1, 'Evaluasi kinerja Agustus 2026', 'diskusi', ['presenter_id' => $this->u['dirkeu']->id, 'discussion' => 'Kinerja Agustus sesuai prognosa.']);
        $a2 = $this->agenda($m, 2, 'Rekonsiliasi persediaan konsinyasi KFTD', 'keputusan', ['presenter_id' => $this->u['akuntansi']->id, 'owner_id' => $this->u['akuntansi']->id,
            'objective' => 'Menetapkan langkah rekonsiliasi persediaan konsinyasi sebelum laporan triwulan III.', 'alternatives' => 'Rekonsiliasi internal / audit khusus pihak ketiga.', 'financial_impact' => 'Potensi selisih Rp12 miliar.', 'risks' => 'Salah saji persediaan.', 'recommendation' => 'Rekonsiliasi internal, diaudit SPI.', 'authority_basis' => 'Kewenangan Direksi',
            'discussion' => 'Direksi sepakat rekonsiliasi diselesaikan sebelum akhir Oktober.']);
        $a3 = $this->agenda($m, 3, 'Kebijakan diskon distribusi triwulan IV', 'keputusan', ['presenter_id' => $this->u['dirkom']->id, 'objective' => 'Menetapkan batas diskon.', 'alternatives' => '3% / 5%.', 'financial_impact' => 'Margin distribusi turun 0,4 poin.', 'risks' => 'Perang harga.', 'recommendation' => '3%.', 'authority_basis' => 'Board Manual 3.2', 'discussion' => 'Disepakati batas 3%.']);
        $this->publish($m, '2026-09-02 16:00', 'sekper');
        $all = ['dirut' => 'setuju', 'dirkeu' => 'setuju', 'dirprod' => 'setuju', 'dirkom' => 'setuju', 'dirsdm' => 'setuju', 'dirport' => 'setuju'];
        $this->x['_d_rekon'] = $this->decide($a2, DecisionKind::KeputusanDireksi, 'Direksi menugaskan VP Akuntansi menyelesaikan rekonsiliasi persediaan konsinyasi KFTD paling lambat 31 Oktober 2026 dan SPI melakukan reviu atas hasilnya.', $all);
        $this->x['_d_diskon'] = $this->decide($a3, DecisionKind::KeputusanDireksi, 'Direksi menetapkan batas diskon distribusi triwulan IV 2026 sebesar 3% dari harga daftar.', [], [], ['method' => 'musyawarah']);
        $this->minutesSigned($m);
    }

    protected function direksiOctober(): void
    {
        $m = $this->meeting('direksi', '2026-10-13 09:00', 'Rapat Direksi Oktober 2026', 'RD/10/2026', ['guests' => ['scm' => 'presenter']]);
        $a1 = $this->agenda($m, 1, 'Evaluasi kinerja September 2026', 'diskusi', ['presenter_id' => $this->u['dirkeu']->id, 'discussion' => 'Penjualan September Rp790 miliar; arus kas operasi positif.']);
        $a2 = $this->agenda($m, 2, 'Rencana peningkatan utilisasi pabrik (konsolidasi 9 menjadi 7 pabrik)', 'keputusan', ['presenter_id' => $this->u['scm']->id, 'owner_id' => $this->u['dirprod']->id,
            'objective' => 'Memutuskan rencana konsolidasi produksi.', 'background' => 'Utilisasi rata-rata 52% menekan biaya tetap.', 'alternatives' => 'A: konsolidasi 2 pabrik; B: maklon pihak ketiga.', 'financial_impact' => 'Penghematan Rp85 miliar per tahun mulai 2028.', 'risks' => 'Gangguan pasokan, izin BPOM, dampak SDM.', 'recommendation' => 'Alternatif A.', 'authority_basis' => 'Kewenangan Direksi; nasihat Dekom wajib diminta (Board Manual 4.4)',
            'discussion' => 'Direktur Produksi menyampaikan keberatan atas jadwal transfer teknologi 18 bulan yang dinilai terlalu cepat. Direktur SDM abstain karena kajian dampak SDM belum final.']);
        $a3 = $this->agenda($m, 3, 'Penyesuaian harga jual produk generik tertentu', 'keputusan', ['presenter_id' => $this->u['dirkom']->id, 'objective' => 'Penyesuaian harga 12 SKU.', 'alternatives' => 'Naik 4% / tetap.', 'financial_impact' => 'Tambahan laba bruto Rp9 miliar per tahun.', 'risks' => 'Penurunan volume.', 'recommendation' => 'Naik 4%.', 'authority_basis' => 'Board Manual 3.2', 'discussion' => 'Disepakati.']);
        $this->attach($a2, 'utilisasi');
        $this->publish($m, '2026-10-07 15:00', 'sekper');

        $this->x['_d_utilisasi'] = $this->decide($a2, DecisionKind::KeputusanDireksi,
            'Direksi menyetujui rencana konsolidasi produksi dari 9 menjadi 7 pabrik aktif (Alternatif A) dan meminta nasihat Dewan Komisaris sebelum pelaksanaan. Direktorat Produksi menyusun rencana rinci transfer teknologi paling lambat 30 November 2026.',
            ['dirut' => 'setuju', 'dirkeu' => 'setuju', 'dirprod' => 'tidak_setuju', 'dirkom' => 'setuju', 'dirsdm' => 'abstain', 'dirport' => 'setuju'],
            ['dirprod' => 'Jadwal transfer teknologi 18 bulan terlalu singkat untuk dua lini steril; diusulkan 24 bulan.', 'dirsdm' => 'Abstain sampai kajian dampak SDM selesai.']);
        $this->x['_d_harga'] = $this->decide($a3, DecisionKind::KeputusanDireksi, 'Direksi menyetujui kenaikan harga jual 12 SKU generik sebesar 4% mulai 1 November 2026.', [], [], ['method' => 'musyawarah']);

        // Risalah belum lengkap: satu anggota tidak menandatangani dengan alasan, satu belum menanggapi.
        $this->minutesSigned($m, ['dirprod' => 'Catatan perbedaan pendapat saya pada agenda 2 belum dimuat lengkap; mohon dilengkapi sebelum saya menandatangani.'], ['dirsdm']);
    }

    protected function auditCommitteeOctober(): void
    {
        $m = $this->meeting('komite_audit', '2026-10-20 10:00', 'Rapat Komite Audit Oktober 2026', 'RKA/04/2026', ['guests' => ['spi' => 'presenter', 'akuntansi' => 'presenter']]);
        $a1 = $this->agenda($m, 1, 'Telaah laporan keuangan triwulan III 2026', 'diskusi', ['presenter_id' => $this->u['akuntansi']->id, 'discussion' => 'Komite meminta rekonsiliasi persediaan diselesaikan sebelum penerbitan.']);
        $a2 = $this->agenda($m, 2, 'Temuan SPI berulang pada pengadaan bahan baku', 'keputusan', ['presenter_id' => $this->u['spi']->id, 'discussion' => 'Tiga temuan berulang.']);
        $a1->update(['materials_submitted_at' => '2026-10-19 08:00:00']);
        $a2->update(['materials_submitted_at' => '2026-10-19 08:00:00']);
        $this->attach($a1, 'laporan_ka');
        // Bahan terlambat: terbit H-1 (aturan H-5).
        $this->publish($m, '2026-10-19 11:00', 'sekdekom', 'Bahan dari Direktorat Keuangan baru diterima 19 Oktober.');
        $this->x['_d_spi'] = $this->decide($a2, DecisionKind::RekomendasiKomite, 'Komite Audit merekomendasikan Dewan Komisaris meminta Direksi memperkuat verifikasi vendor dan memisahkan fungsi pemesanan dan penerimaan, dengan audit tindak lanjut SPI pada Desember 2026.');
        $this->at('2026-10-21 10:00', 'sekdekom');
        app(MinuteService::class)->createDraft($m->fresh(), $this->u['sekdekom']);
    }

    protected function knrJuly(): void
    {
        $m = $this->meeting('komite_nr', '2026-07-07 13:00', 'Rapat Komite Nominasi dan Remunerasi Juli 2026', 'RKNR/02/2026', ['closed' => true]);
        $a = $this->agenda($m, 1, 'Usulan remunerasi 2027', 'keputusan', ['classification' => 'sangat_rahasia', 'presenter_id' => $this->u['kom2']->id, 'discussion' => 'Komite sepakat remunerasi tidak naik.']);
        $this->attach($a, 'remunerasi');
        $this->publish($m, '2026-07-01 10:00', 'sekdekom');
        $this->decide($a, DecisionKind::RekomendasiKomite, 'KNR merekomendasikan remunerasi 2027 tidak dinaikkan dan tantiem tidak dibayarkan selama perusahaan membukukan rugi.');
        $this->minutesSigned($m);
    }

    /** Rapat unggulan demo: 29 Oktober 2026, bahan sudah terbit dan diperbarui setelah distribusi. */
    protected function jointMeetingQ3(): void
    {
        $m = $this->meeting('gabungan', '2026-10-29 09:00', 'Rapat Gabungan Dewan Komisaris dan Direksi — Kinerja Triwulan III dan RKAP 2027', 'RGAB/04/2026', [
            'status' => MeetingStatus::BahanTerbit, 'hours' => 5,
            'guests' => ['konsultan' => 'presenter', 'treasury' => 'presenter', 'asetvp' => 'presenter'],
            'rsvp' => ['kom4' => 'ragu', 'dirsdm' => 'tidak_hadir'],
        ]);
        $this->x['_m_q3'] = $m;
        $m->participants()->where('user_id', $this->u['dirsdm']->id)->update(['rsvp_note' => 'Dinas ke Plant Banjaran; diwakili laporan tertulis.']);

        $this->agenda($m, 1, 'Pembukaan dan pengesahan risalah rapat gabungan 25 Agustus 2026', 'informasi', ['duration_min' => 10, 'classification' => 'internal', 'objective' => 'Mengesahkan risalah rapat sebelumnya.']);
        $a2 = $this->agenda($m, 2, 'Kinerja konsolidasian triwulan III 2026 dan pemenuhan kewajiban pasca restrukturisasi', 'diskusi', ['duration_min' => 60,
            'presenter_id' => $this->u['konsultan']->id, 'owner_id' => $this->u['treasury']->id,
            'objective' => 'Menelaah capaian triwulan III dan kesiapan pembayaran kewajiban 2026–2028.',
            'background' => 'Restrukturisasi dengan 11 bank selesai 16 Desember 2025 (LT 2025 hlm. 8). Pembayaran pokok pertama Desember 2026.']);
        $a3 = $this->agenda($m, 3, 'Usulan RKAP 2027', 'persetujuan', ['duration_min' => 90, 'deciding_organ' => 'dekom',
            'presenter_id' => $this->u['dirkeu']->id, 'owner_id' => $this->u['dirut']->id,
            'objective' => 'Memperoleh persetujuan Dewan Komisaris atas RKAP 2027.',
            'background' => 'Penjualan 2025 turun 7,20% dan rugi menyempit 63,30% (hitung aplikasi atas LT 2025 hlm. 16).',
            'alternatives' => 'Skenario dasar (target Rp9,9 triliun) dan skenario konservatif (Rp9,4 triliun).',
            'financial_impact' => 'Laba bersih Rp12 miliar; capex Rp360 miliar.',
            'risks' => 'Sensitivitas penjualan −5% menghapus kemampuan bayar pokok 2027 tanpa optimalisasi aset.',
            'recommendation' => 'Menyetujui RKAP 2027 skenario dasar dengan syarat pemantauan covenant bulanan.',
            'authority_basis' => 'Anggaran Dasar — RKAP memerlukan persetujuan Dewan Komisaris']);
        $a4 = $this->agenda($m, 4, 'Optimalisasi aset non-produktif 2027', 'persetujuan', ['duration_min' => 45, 'deciding_organ' => 'dekom', 'classification' => 'sangat_rahasia',
            'presenter_id' => $this->u['asetvp']->id, 'owner_id' => $this->u['dirkeu']->id,
            'objective' => 'Persetujuan prinsip penjualan/kerja sama 7 aset non-produktif.',
            'background' => 'Target hasil Rp250 miliar menjadi sumber pembayaran pokok 2027.',
            'financial_impact' => 'Nilai buku Rp180 miliar; target hasil Rp250 miliar.',
            'recommendation' => 'Persetujuan prinsip; persetujuan per transaksi menyusul.']);
        $this->agenda($m, 5, 'Lain-lain', 'informasi', ['duration_min' => 15, 'classification' => 'internal']);
        $a2->update(['materials_submitted_at' => '2026-10-20 17:00:00']);
        $a3->update(['materials_submitted_at' => '2026-10-20 17:00:00']);
        $a4->update(['materials_submitted_at' => '2026-10-21 12:00:00']);

        $this->attach($a2, 'kinerja_q3', 'kewajiban', 'lt2025');
        $this->attach($a3, 'rkap_v1');
        $this->attach($a4, 'aset');

        // Benturan kepentingan: anggota mundur dari agenda 4; bahan tertutup baginya.
        $this->at('2026-10-21 15:00', 'kom3');
        ConflictDeclaration::create(['agenda_item_id' => $a4->id, 'user_id' => $this->u['kom3']->id, 'recused' => true, 'counts_for_quorum' => false, 'declared_at' => now(),
            'nature' => 'memiliki hubungan keluarga dengan pemegang saham calon mitra kerja sama aset Jakarta Timur']);
        Audit::log('benturan_kepentingan', 'Demo Komisaris B menyatakan benturan kepentingan dan mundur dari agenda 4', $a4);

        $this->publish($m, '2026-10-22 09:00', 'sekper');

        // Board book diperbarui setelah distribusi: RKAP versi 2 menggantikan versi 1.
        $this->at('2026-10-23 15:30', 'sekper');
        $a3->documents()->detach($this->d['rkap_v1']->id);
        $a3->documents()->attach($this->d['rkap_v2']->id);
        $this->publish($m, '2026-10-23 16:00', 'sekper', 'RKAP 2027 diperbarui oleh Direktorat Keuangan setelah rasionalisasi prinsipal distribusi.');

        // Siapa sudah membuka versi mana.
        $books = $m->boardBooks()->orderBy('version_no')->get();
        foreach (['komut', 'kom1', 'kom2', 'kom3', 'dirut', 'dirkeu', 'dirkom'] as $k) {
            \App\Models\BoardBookRead::create(['board_book_id' => $books[0]->id, 'user_id' => $this->u[$k]->id, 'opened_at' => '2026-10-22 '.rand(10, 20).':15:00']);
        }
        foreach (['komut', 'kom1', 'dirut', 'dirkeu'] as $k) {
            \App\Models\BoardBookRead::create(['board_book_id' => $books[1]->id, 'user_id' => $this->u[$k]->id, 'opened_at' => '2026-10-24 0'.rand(7, 9).':20:00']);
        }

        Annotation::create(['document_id' => $this->d['rkap_v2']->id, 'user_id' => $this->u['komut']->id, 'page_no' => 1, 'kind' => 'catatan', 'visibility' => 'pribadi',
            'quote' => 'Target penjualan neto 2027 Rp9.900 miliar', 'body' => 'Bandingkan dengan tren 2025 yang turun 7,2%. Apakah +7,3% realistis?']);
        Annotation::create(['document_id' => $this->d['kinerja_q3']->id, 'user_id' => $this->u['kom1']->id, 'page_no' => 3, 'kind' => 'pertanyaan', 'visibility' => 'bersama',
            'quote' => 'Rasio lancar 0,61 kali', 'body' => 'Satuan rasio lancar di laporan tahunan tertulis 56,37 x. Mohon diseragamkan.']);
        Annotation::create(['document_id' => $this->d['kewajiban']->id, 'user_id' => $this->u['kom1']->id, 'page_no' => 3, 'kind' => 'penanda', 'visibility' => 'pribadi',
            'quote' => 'Nilai ambang (threshold) dan headroom per kreditur BELUM dicantumkan', 'body' => 'Minta rincian covenant.']);
    }

    protected function upcomingMeetings(): void
    {
        $this->at('2026-10-15 10:00', 'sekper');
        $m = $this->meeting('direksi', '2026-11-10 09:00', 'Rapat Direksi November 2026', 'RD/11/2026', ['status' => MeetingStatus::Dijadwalkan]);
        $this->agenda($m, 1, 'Evaluasi kinerja Oktober 2026', 'diskusi', ['presenter_id' => $this->u['dirkeu']->id]);
        $this->agenda($m, 2, 'Rencana rinci transfer teknologi konsolidasi pabrik', 'keputusan', ['presenter_id' => $this->u['dirprod']->id]);
        $m->agendaItems()->update(['materials_submitted_at' => null]);

        $this->at('2026-10-20 10:00', 'sekdekom');
        $d = $this->meeting('dekom', '2026-11-17 13:00', 'Rapat Dewan Komisaris November 2026', 'RDK/06/2026', ['status' => MeetingStatus::Dijadwalkan]);
        $this->agenda($d, 1, 'Persetujuan RKAP 2027 (tindak lanjut rapat gabungan)', 'persetujuan');
        $this->agenda($d, 2, 'Nasihat atas rencana konsolidasi pabrik', 'nasihat');
        $this->agenda($d, 3, 'Evaluasi kinerja Direksi 2026 (persiapan)', 'diskusi');
        $d->agendaItems()->update(['materials_submitted_at' => null]);
        $this->x['_m_dk_nov'] = $d;

        $k = $this->meeting('komite_nr', '2026-11-03 10:00', 'Rapat Komite Nominasi dan Remunerasi November 2026', 'RKNR/03/2026', ['status' => MeetingStatus::Dijadwalkan, 'closed' => true]);
        $this->agenda($k, 1, 'Rencana suksesi Direksi entitas anak', 'diskusi', ['classification' => 'sangat_rahasia']);
    }

    protected function circulars(): void
    {
        $svc = app(CircularService::class);
        $make = function (string $organ, string $number, string $title, string $body, string $circulated, string $deadline) {
            $this->at($circulated, $this->secretaryOf($organ));

            return CircularResolution::create(['organ_id' => $this->o[$organ]->id, 'number' => $number, 'title' => $title, 'body' => $body,
                'deadline_at' => $deadline, 'created_by' => $this->u[$this->secretaryOf($organ)]->id, 'status' => 'draf']);
        };
        $respond = function (CircularResolution $c, array $answers) use ($svc) {
            foreach ($answers as $k => [$when, $ok, $note]) {
                $this->at($when, $k);
                $svc->respond($c->fresh(), $this->u[$k], $ok, $note);
            }
        };

        $c1 = $make('dekom', 'SRK-DK/01/2026', 'Persetujuan usulan penunjukan KAP untuk audit tahun buku 2026', 'Dewan Komisaris menyetujui usulan kepada RUPS untuk menunjuk KAP sesuai rekomendasi Komite Audit untuk mengaudit laporan keuangan tahun buku 2026.', '2026-04-02 10:00', '2026-04-09 17:00');
        $svc->circulate($c1);
        $respond($c1, ['komut' => ['2026-04-03 09:00', true, null], 'kom1' => ['2026-04-03 11:00', true, 'Sesuai rekomendasi Komite Audit.'], 'kom2' => ['2026-04-04 08:30', true, null], 'kom3' => ['2026-04-06 13:00', true, null], 'kom4' => ['2026-04-07 10:00', true, null]]);

        $c2 = $make('direksi', 'SRK-DIR/03/2026', 'Penetapan kebijakan harga obat program pemerintah 2026', 'Direksi menetapkan harga penawaran obat program pemerintah 2026 sesuai lampiran.', '2026-10-08 10:00', '2026-10-15 17:00');
        $svc->circulate($c2);
        $respond($c2, ['dirut' => ['2026-10-08 14:00', true, null], 'dirkeu' => ['2026-10-09 09:00', true, null], 'dirkom' => ['2026-10-09 10:00', true, null], 'dirport' => ['2026-10-12 16:00', true, null]]);
        $this->at('2026-10-16 07:00', 'sekper');
        $svc->evaluate($c2->fresh()); // kedaluwarsa: 2 anggota tidak menanggapi

        $c3 = $make('direksi', 'SRK-DIR/02/2026', 'Penghapusbukuan persediaan kedaluwarsa gudang regional', 'Direksi menyetujui penghapusbukuan persediaan kedaluwarsa senilai Rp8,7 miliar.', '2026-07-20 10:00', '2026-07-27 17:00');
        $svc->circulate($c3);
        $respond($c3, ['dirut' => ['2026-07-21 09:00', true, null], 'dirprod' => ['2026-07-21 15:00', false, 'Data stok fisik belum diverifikasi SPI; usulkan dibahas di rapat.']]);

        $c4 = $make('dekom', 'SRK-DK/02/2026', 'Persetujuan perubahan struktur organisasi PT Kimia Farma Apotek', 'Dewan Komisaris menyetujui perubahan struktur organisasi entitas anak KFA sesuai usulan Direksi.', '2026-10-21 10:00', '2026-10-31 17:00');
        $svc->circulate($c4);
        $respond($c4, ['kom1' => ['2026-10-22 09:00', true, null], 'kom2' => ['2026-10-22 13:00', true, null], 'kom4' => ['2026-10-23 08:00', true, 'Mohon pastikan dampak pada layanan klinik dipantau.']]);
    }

    protected function tasks(): void
    {
        $ts = app(TaskService::class);
        $n = 0;
        $make = function (array $a) use (&$n) {
            $n++;
            $this->at($a['created'] ?? '2026-09-10 10:00', 'sekper');
            $task = Task::create([
                'company_id' => $this->kaef->id, 'code' => sprintf('TL-2026-%03d', $n), 'title' => $a['title'], 'description' => $a['desc'] ?? null,
                'decision_id' => $a['decision']?->id, 'agenda_item_id' => $a['decision']?->agenda_item_id,
                'pic_id' => $this->u[$a['pic']]->id, 'verifier_id' => $this->u[$a['verifier']]->id, 'due_date' => $a['due'], 'original_due_date' => $a['original_due'] ?? $a['due'],
                'priority' => $a['priority'] ?? 'sedang', 'progress' => $a['progress'] ?? 0, 'status' => $a['status'] ?? 'belum_mulai',
            ]);
            app(TaskService::class)->event($task, 'dibuat', 'Tugas dibuat dari '.($a['decision']?->number ?? 'rapat'), $this->u['sekper']);
            foreach ($a['milestones'] ?? [] as [$title, $due, $done]) {
                $task->milestones()->create(['title' => $title, 'due_date' => $due, 'is_done' => $done]);
            }

            return $task;
        };

        $t1 = $make(['title' => 'Menyusun proyeksi arus kas 13 minggu dan melaporkannya setiap bulan', 'decision' => $this->x['_d_likuiditas'], 'pic' => 'treasury', 'verifier' => 'kom1', 'due' => '2026-11-15', 'priority' => 'tinggi', 'created' => '2026-09-23 10:00',
            'milestones' => [['Model proyeksi disetujui Direktur Keuangan', '2026-10-05', true], ['Laporan Oktober', '2026-10-31', false], ['Laporan November', '2026-11-15', false]]]);
        $this->at('2026-10-06 10:00', 'treasury');
        $ts->updateProgress($t1, 60, 'Model proyeksi disetujui; laporan bulanan pertama disampaikan 6 Oktober.', $this->u['treasury']);

        // Bukti ditolak, lalu dikerjakan ulang.
        $t2 = $make(['title' => 'Rekonsiliasi persediaan konsinyasi KFTD', 'decision' => $this->x['_d_rekon'], 'pic' => 'akuntansi', 'verifier' => 'dirkeu', 'due' => '2026-10-31', 'priority' => 'tinggi', 'created' => '2026-09-09 10:00']);
        $this->at('2026-09-20 10:00', 'akuntansi');
        $ts->updateProgress($t2, 70, 'Rekonsiliasi gudang pusat selesai.', $this->u['akuntansi']);
        $this->at('2026-10-09 15:00', 'akuntansi');
        $ev = $ts->submit($t2->fresh(), 'Rekonsiliasi saldo konsinyasi gudang pusat KFTD per 30 September 2026.', $this->d['bukti_rekon']->id, null, $this->u['akuntansi']);
        $this->at('2026-10-12 09:00', 'dirkeu');
        $ts->verify($ev, false, 'Ditolak: rekonsiliasi belum mencakup gudang cabang dan belum direviu SPI sebagaimana keputusan Direksi.', $this->u['dirkeu']);
        $this->at('2026-10-20 16:00', 'akuntansi');
        $ts->updateProgress($t2->fresh(), 85, 'Gudang cabang wilayah barat selesai; wilayah timur berjalan.', $this->u['akuntansi']);

        // Menunggu verifikasi.
        $t3 = $make(['title' => 'Rencana rinci transfer teknologi konsolidasi pabrik', 'decision' => $this->x['_d_utilisasi'], 'pic' => 'scm', 'verifier' => 'dirut', 'due' => '2026-11-30', 'priority' => 'tinggi', 'created' => '2026-10-14 09:00']);
        $this->at('2026-10-22 17:00', 'scm');
        $ts->submit($t3, 'Draf rencana transfer teknologi 24 bulan untuk lini steril dan 18 bulan untuk lini non-steril, mengakomodasi catatan Direktur Produksi.', $this->d['utilisasi']->id, null, $this->u['scm']);

        // Terlambat.
        $make(['title' => 'Laporan progres penjualan aset non-produktif', 'decision' => $this->x['_d_medan'], 'pic' => 'asetvp', 'verifier' => 'kom1', 'due' => '2026-09-30', 'progress' => 40, 'status' => 'berjalan', 'priority' => 'tinggi', 'created' => '2026-09-23 11:00']);

        // Selesai terverifikasi.
        $t5 = $make(['title' => 'Menyusun SOP penyampaian bahan rapat paling lambat H-5', 'pic' => 'sekper', 'verifier' => 'dirut', 'due' => '2026-07-31', 'created' => '2026-06-10 10:00', 'decision' => null]);
        $this->at('2026-07-25 10:00', 'sekper');
        $ev5 = $ts->submit($t5, 'SOP Sekretariat No. 07/2026 tentang penyampaian bahan rapat telah ditandatangani.', $this->d['boardmanual']->id, null, $this->u['sekper']);
        $this->at('2026-07-28 10:00', 'dirut');
        $ts->verify($ev5, true, 'Sesuai. Mohon disosialisasikan ke seluruh direktorat.', $this->u['dirut']);

        // Dibuka kembali.
        $t6 = $make(['title' => 'Tindak lanjut temuan SPI pengadaan bahan baku', 'decision' => $this->x['_d_spi'], 'pic' => 'spi', 'verifier' => 'kom1', 'due' => '2026-12-15', 'created' => '2026-07-29 10:00']);
        $this->at('2026-08-30 10:00', 'spi');
        $ev6 = $ts->submit($t6, 'Laporan penutupan 3 temuan pengadaan semester I.', null, null, $this->u['spi']);
        $this->at('2026-09-02 10:00', 'kom1');
        $ts->verify($ev6, true, 'Diterima.', $this->u['kom1']);
        $this->at('2026-10-20 15:00', 'kom1');
        $ts->reopen($t6->fresh(), 'Temuan yang sama muncul kembali pada rapat Komite Audit 20 Oktober 2026; penutupan sebelumnya belum efektif.', $this->u['kom1']);

        $t7 = $make(['title' => 'Kajian alternatif pembiayaan modal kerja', 'pic' => 'treasury', 'verifier' => 'dirkeu', 'due' => '2026-08-31', 'created' => '2026-06-24 10:00', 'decision' => null]);
        $this->at('2026-08-20 10:00', 'treasury');
        $ev7 = $ts->submit($t7, 'Kajian 3 alternatif: SCF, anjak piutang pemerintah, dan revolving facility.', null, null, $this->u['treasury']);
        $this->at('2026-08-24 10:00', 'dirkeu');
        $ts->verify($ev7, true, null, $this->u['dirkeu']);

        $make(['title' => 'Sosialisasi penyesuaian harga jual 12 SKU ke cabang distribusi', 'decision' => $this->x['_d_harga'], 'pic' => 'scm', 'verifier' => 'dirkom', 'due' => '2026-11-20', 'created' => '2026-10-14 10:00']);
        $make(['title' => 'Memperbaiki label satuan rasio lancar pada laporan dan dasbor', 'pic' => 'akuntansi', 'verifier' => 'dirkeu', 'due' => '2026-11-30', 'created' => '2026-10-24 08:00', 'decision' => null,
            'desc' => 'Berasal dari permintaan informasi Komisaris tentang satuan 56,37 x pada LT 2025 hlm. 19.']);

        // Kemajuan 100% tetapi belum diajukan: belum boleh dianggap selesai.
        $t10 = $make(['title' => 'Program peningkatan pendapatan klinik dan laboratorium', 'pic' => 'ritel', 'verifier' => 'dirport', 'due' => '2026-10-31', 'created' => '2026-07-15 10:00', 'decision' => null]);
        $this->at('2026-10-21 10:00', 'ritel');
        $ts->updateProgress($t10, 100, 'Seluruh kegiatan telah dilaksanakan; bukti dalam penyusunan.', $this->u['ritel']);

        $make(['title' => 'Penerapan batas diskon distribusi 3% pada sistem penjualan', 'decision' => $this->x['_d_diskon'], 'pic' => 'scm', 'verifier' => 'dirkom', 'due' => '2026-10-01', 'created' => '2026-09-09 10:00']);
        $t12 = $make(['title' => 'Kontinjensi pasokan bahan baku impor triwulan IV', 'pic' => 'scm', 'verifier' => 'dirprod', 'due' => '2026-10-15', 'created' => '2026-08-26 10:00', 'decision' => null]);
        $this->at('2026-09-25 10:00', 'scm');
        $ts->updateProgress($t12, 50, 'Dua pemasok alternatif dalam kualifikasi.', $this->u['scm']);
    }

    protected function informationRequests(): void
    {
        $svc = app(InformationRequestService::class);
        $m = $this->x['_m_q3'];
        $items = $m->agendaItems()->orderBy('seq')->get();
        $n = 10;
        $make = function (array $a) use (&$n, $m) {
            $n++;
            $this->at($a['at'], $a['by']);
            $r = InformationRequest::create([
                'company_id' => $this->kaef->id, 'organ_id' => $this->o[$a['organ'] ?? 'dekom']->id, 'meeting_id' => $a['meeting'] ?? $m->id, 'agenda_item_id' => $a['agenda'] ?? null,
                'document_id' => isset($a['doc']) ? $this->d[$a['doc']]->id : null, 'page_no' => $a['page'] ?? null,
                'code' => sprintf('PI-2026-%03d', $n), 'subject' => $a['subject'], 'question' => $a['question'],
                'requester_id' => $this->u[$a['by']]->id, 'pic_id' => $this->u[$a['pic']]->id, 'priority' => $a['priority'] ?? 'sedang',
                'sla_due_at' => $a['sla'], 'status' => 'diajukan',
            ]);
            $r->messages()->create(['user_id' => $this->u[$a['by']]->id, 'kind' => 'pertanyaan', 'body' => $a['question']]);
            \Filament\Notifications\Notification::make()->title('Permintaan informasi baru')->body($r->subject)->sendToDatabase($this->u[$a['pic']]);
            Audit::log('info_diajukan', "Permintaan informasi {$r->code}: {$r->subject}", $r);

            return $r;
        };

        $r1 = $make(['by' => 'komut', 'pic' => 'akuntansi', 'at' => '2026-10-22 14:00', 'sla' => '2026-10-26 17:00', 'agenda' => $items[1]->id, 'doc' => 'lt2025', 'page' => 19, 'priority' => 'tinggi',
            'subject' => 'Klarifikasi satuan rasio lancar 2025', 'question' => 'Tabel rasio LT 2025 hlm. 19 menampilkan rasio lancar 56,37 dengan satuan x (kali), sedangkan aset lancar dibagi liabilitas jangka pendek hanya 0,56 kali. Mohon klarifikasi satuan yang benar dan apakah perlu ralat.']);
        $this->at('2026-10-23 10:30', 'akuntansi');
        $svc->answer($r1, $this->u['akuntansi'], 'Benar, nilai yang dimaksud adalah 56,37% atau 0,5637 kali. Label "x" pada tabel rasio adalah kekeliruan penyajian. Kami akan menyeragamkan penyajian pada laporan triwulanan dan dasbor (lihat tugas TL-2026-009).', $this->d['lt2025']->id);

        $make(['by' => 'kom1', 'pic' => 'treasury', 'at' => '2026-10-23 09:00', 'sla' => '2026-10-27 17:00', 'agenda' => $items[1]->id, 'doc' => 'kewajiban', 'page' => 2, 'priority' => 'tinggi',
            'subject' => 'Jadwal pembayaran pokok dan bunga 2026–2028 per kreditur', 'question' => 'Mohon jadwal pembayaran pokok dan bunga per kreditur 2026–2028 beserta sumber dana tiap pembayaran, termasuk ketergantungan pada hasil optimalisasi aset.']);

        $r3 = $make(['by' => 'kom3', 'pic' => 'dirkeu', 'at' => '2026-10-21 16:00', 'sla' => '2026-10-24 17:00', 'agenda' => $items[2]->id, 'doc' => 'kewajiban', 'page' => 3, 'priority' => 'tinggi',
            'subject' => 'Rincian covenant dan headroom', 'question' => 'Dokumen kewajiban belum mencantumkan nilai ambang covenant dan headroom. Mohon rinciannya per kreditur dan posisi per 30 September 2026.']);
        $this->at('2026-10-22 18:00', 'dirkeu');
        $svc->answer($r3, $this->u['dirkeu'], 'Covenant utama: DER maksimal 4,5 kali dan DSCR minimal 1,0 kali mulai 2027. Posisi September masih dalam batas.');
        $this->at('2026-10-23 08:00', 'kom3');
        $svc->clarify($r3->fresh(), $this->u['kom3'], 'Mohon angka posisi aktual DER dan DSCR per 30 September serta headroom-nya, bukan hanya pernyataan "dalam batas".');

        $make(['by' => 'kom2', 'pic' => 'asetvp', 'at' => '2026-10-13 10:00', 'sla' => '2026-10-20 17:00', 'agenda' => $items[3]->id, 'doc' => 'aset', 'page' => 2,
            'subject' => 'Penilaian KJPP untuk 3 aset yang belum dinilai', 'question' => 'Kapan penilaian KJPP untuk 3 aset sisanya selesai? Tanpa penilaian, persetujuan prinsip sulit diberikan.']);

        $r5 = $make(['by' => 'kom1', 'pic' => 'treasury', 'organ' => 'dekom', 'meeting' => null, 'at' => '2026-09-23 09:00', 'sla' => '2026-09-30 17:00',
            'subject' => 'Sumber dana cadangan pembayaran pokok Desember 2026', 'question' => 'Mohon penjelasan sumber dana cadangan bila kas operasi tidak mencukupi untuk pembayaran pokok Rp150 miliar.']);
        $this->at('2026-09-29 14:00', 'treasury');
        $svc->answer($r5, $this->u['treasury'], 'Fasilitas revolving Rp200 miliar yang telah disetujui bank kreditur utama dapat ditarik bila diperlukan.');
        $this->at('2026-10-01 09:00', 'kom1');
        $svc->accept($r5->fresh(), $this->u['kom1'], 'Terima kasih, jelas.');

        $make(['by' => 'ka1', 'pic' => 'akuntansi', 'organ' => 'komite_audit', 'meeting' => null, 'at' => '2026-10-20 13:00', 'sla' => '2026-10-30 17:00', 'doc' => 'laporan_ka', 'page' => 2,
            'subject' => 'Penurunan cadangan kerugian piutang pemerintah', 'question' => 'Mohon dasar perhitungan penurunan cadangan kerugian penurunan nilai piutang pemerintah pada triwulan III.']);
    }

    protected function risksAndCompliance(): void
    {
        $c = $this->kaef->id;
        $rows = [
            ['R-01', 'Likuiditas dan pemenuhan kewajiban pasca restrukturisasi', 'keuangan', 'dirkeu', 4, 5, 'tetap', 'Rasio lancar', '0,56 kali (2025)', '≥ 0,80 kali', 'merah', 'Proyeksi kas 13 minggu, fasilitas revolving, optimalisasi aset.', 'derived_from_report'],
            ['R-02', 'Penurunan penjualan distribusi dan ritel', 'strategis', 'dirkom', 4, 4, 'membaik', 'Pertumbuhan penjualan', '−7,20% (2025)', '≥ 0%', 'merah', 'Rasionalisasi prinsipal, penguatan portofolio bermarjin.', 'derived_from_report'],
            ['R-03', 'Utilisasi pabrik rendah', 'operasional', 'dirprod', 4, 3, 'tetap', 'Utilisasi rata-rata', '52%', '≥ 65%', 'kuning', 'Konsolidasi 9 menjadi 7 pabrik.', 'simulated'],
            ['R-04', 'Kepatuhan CPOB dan mutu produk', 'kepatuhan', 'dirprod', 2, 5, 'tetap', 'Temuan kritis BPOM', '0', '0', 'hijau', 'Audit mutu internal triwulanan.', 'simulated'],
            ['R-05', 'Pelindungan data pribadi pasien klinik dan laboratorium', 'kepatuhan', 'dirport', 3, 4, 'memburuk', 'Insiden data', '1 (minor)', '0', 'kuning', 'Penilaian dampak UU PDP, enkripsi data pasien.', 'simulated'],
            ['R-06', 'Ketergantungan bahan baku impor dan kurs', 'operasional', 'dirprod', 4, 3, 'tetap', 'Porsi bahan baku impor', '78%', '≤ 70%', 'kuning', 'Kualifikasi pemasok alternatif.', 'simulated'],
            ['R-07', 'Piutang pemerintah dan program JKN', 'keuangan', 'dirkeu', 3, 4, 'membaik', 'Umur piutang > 90 hari', 'Rp410 miliar', '≤ Rp350 miliar', 'kuning', 'Anjak piutang dan penagihan terpadu.', 'simulated'],
            ['R-08', 'Implementasi ERP terintegrasi', 'teknologi', 'dirport', 3, 3, 'tetap', 'Kemajuan proyek', '64%', '≥ 70%', 'hijau', 'PMO dan uji paralel.', 'simulated'],
        ];
        foreach ($rows as $r) {
            Risk::create(['company_id' => $c, 'code' => $r[0], 'title' => $r[1], 'category' => $r[2], 'owner_id' => $this->u[$r[3]]->id, 'likelihood' => $r[4], 'impact' => $r[5],
                'trend' => $r[6], 'kri_name' => $r[7], 'kri_value' => $r[8], 'kri_threshold' => $r[9], 'kri_status' => $r[10], 'mitigation' => $r[11], 'data_origin' => $r[12]]);
        }

        foreach ([
            ['kewajiban', 'Penyampaian Laporan Tahunan 2025 kepada OJK', 'POJK 29/2016', 'sekper', '2026-04-30', 'selesai', 'sedang'],
            ['kewajiban', 'Penyampaian laporan keuangan triwulan III 2026 kepada kreditur', 'Perjanjian restrukturisasi', 'dirkeu', '2026-10-31', 'terbuka', 'tinggi'],
            ['kewajiban', 'Pengungkapan rapat dan kehadiran organ pada Laporan Tahunan 2026', 'POJK 33/2014', 'sekper', '2027-04-30', 'terbuka', 'sedang'],
            ['kewajiban', 'Reviu Board Manual (siklus 2 tahunan)', 'Board Manual 1.4', 'sekdekom', '2026-12-31', 'dalam_perbaikan', 'sedang'],
            ['temuan', 'Bahan Rapat Komite Audit 20 Oktober 2026 disampaikan H-1 (aturan H-5)', 'POJK 33/2014; SOP Sekretariat 07/2026', 'sekdekom', '2026-11-15', 'terbuka', 'sedang'],
            ['temuan', 'Rapat Dewan Komisaris 19 Mei 2026 tidak kuorum', 'Board Manual 2.3', 'sekdekom', '2026-06-30', 'selesai', 'rendah'],
            ['temuan', 'Temuan SPI berulang pengadaan bahan baku', 'Laporan Komite Audit TW III', 'spi', '2026-12-15', 'dalam_perbaikan', 'tinggi'],
        ] as [$kind, $title, $basis, $owner, $due, $status, $sev]) {
            ComplianceItem::create(['company_id' => $c, 'kind' => $kind, 'title' => $title, 'basis' => $basis, 'owner_id' => $this->u[$owner]->id, 'due_date' => $due, 'status' => $status, 'severity' => $sev]);
        }
    }

    protected function development(): void
    {
        foreach ([
            ['dekom', 2025, 'penilaian_mandiri', 'Penilaian mandiri Dewan Komisaris 2025', 'selesai', 5, 5, ['Struktur dan komposisi' => 4.2, 'Pengawasan strategi' => 3.6, 'Pengawasan risiko' => 3.4, 'Kualitas rapat' => 3.8, 'Hubungan dengan Direksi' => 4.0, 'Pengembangan kompetensi' => 3.5]],
            ['direksi', 2025, 'penilaian_mandiri', 'Penilaian mandiri Direksi 2025', 'selesai', 6, 6, ['Kepemimpinan' => 3.9, 'Pencapaian KPI' => 2.8, 'Manajemen risiko' => 3.3, 'Kualitas bahan rapat' => 3.4, 'Tindak lanjut' => 3.1, 'Kolaborasi' => 3.9]],
            ['komite_audit', 2025, 'penilaian_komite', 'Evaluasi Komite Audit 2025', 'selesai', 3, 3, ['Telaah laporan keuangan' => 4.3, 'Pengawasan audit internal' => 3.9, 'Hubungan dengan KAP' => 4.1, 'Pelaporan ke Dekom' => 4.0]],
            ['gabungan', 2026, 'survei_rapat', 'Survei efektivitas rapat 2026', 'berjalan', 9, 11, ['Ketepatan waktu bahan' => 3.2, 'Kualitas kertas usulan' => 3.4, 'Manajemen waktu rapat' => 3.6, 'Kejelasan keputusan' => 3.9, 'Pemantauan tindak lanjut' => 3.0]],
        ] as [$organ, $year, $kind, $title, $status, $resp, $inv, $scores]) {
            Evaluation::create(['organ_id' => $this->o[$organ]->id, 'year' => $year, 'kind' => $kind, 'title' => $title, 'status' => $status, 'respondents' => $resp, 'invited' => $inv, 'scores' => $scores,
                'summary' => 'Hasil simulasi untuk demonstrasi.']);
        }

        foreach ([
            ['kom4', 'Program pengenalan (onboarding) Komisaris baru', 'Sekretariat Dekom', 'onboarding', '2025-11-20', 12],
            ['dirkeu', 'Program pengenalan (onboarding) Direksi baru', 'Sekretariat Perusahaan', 'onboarding', '2025-11-20', 12],
            ['komut', 'Director Certification Program', 'Lembaga sertifikasi (simulasi)', 'sertifikasi', '2026-03-12', 24],
            ['kom1', 'Pembaruan standar akuntansi PSAK 2026', 'Asosiasi profesi (simulasi)', 'seminar', '2026-02-19', 8],
            ['kom2', 'Tata kelola remunerasi BUMN', 'Lembaga pelatihan (simulasi)', 'pelatihan', '2026-05-07', 6],
            ['kom3', 'Manajemen risiko terintegrasi', 'Lembaga pelatihan (simulasi)', 'pelatihan', '2026-06-18', 16],
            ['dirut', 'Kepemimpinan transformasi', 'Sekolah bisnis (simulasi)', 'pelatihan', '2026-04-23', 16],
            ['dirprod', 'CPOB terkini dan transfer teknologi', 'BPOM (simulasi)', 'seminar', '2026-08-06', 8],
            ['dirsdm', 'Manajemen perubahan dan dampak SDM', 'Lembaga pelatihan (simulasi)', 'pelatihan', '2026-09-10', 12],
            ['ka1', 'Audit berbasis data', 'Asosiasi profesi (simulasi)', 'pelatihan', '2026-07-15', 8],
        ] as [$k, $title, $prov, $kind, $date, $hours]) {
            Training::create(['user_id' => $this->u[$k]->id, 'title' => $title, 'provider' => $prov, 'kind' => $kind, 'held_on' => $date, 'hours' => $hours]);
        }

        foreach (['komut', 'kom1', 'ka1', 'ka2', 'kpr'] as $k) {
            Declaration::create(['user_id' => $this->u[$k]->id, 'year' => 2026, 'kind' => 'independensi', 'status' => 'disampaikan', 'submitted_at' => '2026-01-15 10:00', 'content' => 'Menyatakan tetap memenuhi kriteria independensi.']);
        }
        foreach (['komut', 'kom1', 'kom2', 'kom3', 'kom4', 'dirut', 'dirkeu', 'dirprod', 'dirkom', 'dirsdm', 'dirport'] as $k) {
            $pending = in_array($k, ['kom4', 'dirsdm'], true);
            Declaration::create(['user_id' => $this->u[$k]->id, 'year' => 2026, 'kind' => 'benturan_kepentingan_tahunan', 'status' => $pending ? 'menunggu' : 'disampaikan',
                'submitted_at' => $pending ? null : '2026-01-31 10:00', 'content' => $pending ? null : 'Tidak ada benturan kepentingan selain yang dinyatakan per agenda.']);
        }
    }

    protected function extraAudit(): void
    {
        // Percobaan akses dokumen terlarang (dicatat sistem).
        $this->at('2026-10-23 11:42', 'dirkom');
        Access::deny($this->u['dirkom'], 'membuka "Kertas Usulan Remunerasi Direksi dan Dewan Komisaris 2027" (sangat rahasia, Komite Nominasi dan Remunerasi)', $this->d['remunerasi']);
        $this->at('2026-10-23 14:05', 'kom3');
        Access::deny($this->u['kom3'], 'membuka bahan agenda 4 rapat gabungan 29 Oktober (mundur karena benturan kepentingan)', $this->d['aset']);
        $this->at('2026-10-24 07:30', 'admin');
        Access::deny($this->u['admin'], 'membuka risalah Rapat Dewan Komisaris September 2026 (administrator teknis tanpa hak isi)', null);
    }
}
